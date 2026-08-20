<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\PaymentList;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    public function attendance(Event $event)
    {
        $this->preparePdfGeneration();
        $this->authorize('view', $event);
        $event->load([
            'organization.users.roles',
            'days',
            'participants' => fn ($query) => $query->orderBy('full_name'),
            'attendanceRecords.signature',
        ]);

        $records = $event->attendanceRecords->keyBy(fn ($record) => $record->event_day_id.'-'.$record->participant_id.'-'.$record->type);
        [$settings, $logoData, $secondaryLogosData, $headerBannerData, $managers] = $this->documentOptions($event);

        return Pdf::loadView('exports.attendance', compact('event', 'records', 'settings', 'logoData', 'secondaryLogosData', 'headerBannerData', 'managers'))
            ->setPaper('a4', 'landscape')
            ->download('lista-presenca-'.$event->starts_on->format('Y-m-d').'.pdf');
    }

    public function payment(PaymentList $paymentList)
    {
        $this->preparePdfGeneration();
        $paymentList->load(['event.organization.users.roles', 'payments.participant', 'payments.signature']);
        $event = $paymentList->event;
        $this->authorize('view', $event);
        [$settings, $logoData, $secondaryLogosData, $headerBannerData, $managers] = $this->documentOptions($event);
        $attendanceSignatures = $this->attendanceSignatures($event);

        return Pdf::loadView('exports.payment', compact('event', 'paymentList', 'settings', 'logoData', 'secondaryLogosData', 'headerBannerData', 'managers', 'attendanceSignatures'))
            ->setPaper('a4', 'landscape')
            ->download('lista-recebimento-'.$paymentList->payment_date->format('Y-m-d').'.pdf');
    }

    public function payments(Event $event)
    {
        $this->preparePdfGeneration();
        $this->authorize('view', $event);
        $event->load(['organization.users.roles', 'paymentLists.payments.participant', 'paymentLists.payments.signature']);
        [$settings, $logoData, $secondaryLogosData, $headerBannerData, $managers] = $this->documentOptions($event);
        $attendanceSignatures = $this->attendanceSignatures($event);

        return Pdf::loadView('exports.payments-all', compact('event', 'settings', 'logoData', 'secondaryLogosData', 'headerBannerData', 'managers', 'attendanceSignatures'))
            ->setPaper('a4', 'landscape')
            ->download('listas-pagamento-'.$event->starts_on->format('Y-m-d').'.pdf');
    }

    /**
     * Most recent check-in signature per participant, so payment lists can reuse the
     * signature already collected on the attendance list instead of asking for a new one.
     */
    private function attendanceSignatures(Event $event)
    {
        return $event->attendanceRecords()
            ->where('type', 'check_in')
            ->whereHas('signature')
            ->with('signature')
            ->latest('recorded_at')
            ->get()
            ->groupBy('participant_id')
            ->map(fn ($records) => $records->first()->signature);
    }

    private function documentOptions(Event $event): array
    {
        $organization = $event->organization;
        $settings = $organization->report_settings ?? [];
        $selectedIds = collect($settings['signatory_user_ids'] ?? [])->map(fn ($id) => (int) $id);
        $managers = $selectedIds->isNotEmpty()
            ? $selectedIds->map(fn ($id) => $organization->users->firstWhere('id', $id))->filter()->take(2)
            : $organization->users->filter(fn ($user) => $user->roles->pluck('name')->intersect(['Administrador da Organização', 'Gestor de Eventos', 'Coordenador Geral', 'Coordenadora de campo'])->isNotEmpty())->take(2);

        $logoData = $this->imageData($organization->logo_path, 480, 180);

        $secondaryLogosData = collect($settings['secondary_logo_paths'] ?? [])
            ->take(3)
            ->map(fn ($path) => $this->imageData($path, 360, 160))
            ->filter()
            ->values()
            ->all();

        $headerBannerData = null;
        $headerBannerPath = $settings['header_banner_path'] ?? null;
        $headerBannerData = $this->imageData($headerBannerPath, 1600, 240);

        return [$settings, $logoData, $secondaryLogosData, $headerBannerData, $managers];
    }

    private function imageData(?string $path, int $maxWidth, int $maxHeight): ?string
    {
        return self::optimizedImageData($path, $maxWidth, $maxHeight);
    }

    public static function signatureData(?string $path): ?string
    {
        return self::optimizedImageData($path, 240, 80);
    }

    private static function optimizedImageData(?string $path, int $maxWidth, int $maxHeight): ?string
    {
        static $cache = [];

        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        $cacheKey = $path.'-'.$maxWidth.'x'.$maxHeight;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $binary = Storage::disk('local')->get($path);
        if (! function_exists('imagecreatefromstring')) {
            $mime = Storage::disk('local')->mimeType($path) ?: 'image/png';

            return $cache[$cacheKey] = 'data:'.$mime.';base64,'.base64_encode($binary);
        }

        $source = @imagecreatefromstring($binary);
        if (! $source) {
            $mime = Storage::disk('local')->mimeType($path) ?: 'image/png';

            return $cache[$cacheKey] = 'data:'.$mime.';base64,'.base64_encode($binary);
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
        imagefill($target, 0, 0, $transparent);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        ob_start();
        imagepng($target, null, 6);
        $optimized = ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        if (! is_string($optimized) || $optimized === '') {
            $mime = Storage::disk('local')->mimeType($path) ?: 'image/png';

            return $cache[$cacheKey] = 'data:'.$mime.';base64,'.base64_encode($binary);
        }

        return $cache[$cacheKey] = 'data:image/png;base64,'.base64_encode($optimized);
    }

    private function preparePdfGeneration(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }
    }
}
