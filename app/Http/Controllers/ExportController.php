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
        $this->authorize('view', $event);
        $event->load([
            'organization.users.roles',
            'days',
            'participants' => fn ($query) => $query->orderBy('full_name'),
            'attendanceRecords.signature',
        ]);

        $records = $event->attendanceRecords->keyBy(fn ($record) => $record->event_day_id.'-'.$record->participant_id.'-'.$record->type);
        [$settings, $logoData, $headerBannerData, $managers] = $this->documentOptions($event);

        return Pdf::loadView('exports.attendance', compact('event', 'records', 'settings', 'logoData', 'headerBannerData', 'managers'))
            ->setPaper('a4', 'landscape')
            ->download('lista-presenca-'.$event->starts_on->format('Y-m-d').'.pdf');
    }

    public function payment(PaymentList $paymentList)
    {
        $paymentList->load(['event.organization.users.roles', 'payments.participant', 'payments.signature']);
        $event = $paymentList->event;
        $this->authorize('view', $event);
        [$settings, $logoData, $headerBannerData, $managers] = $this->documentOptions($event);
        $attendanceSignatures = $this->attendanceSignatures($event);

        return Pdf::loadView('exports.payment', compact('event', 'paymentList', 'settings', 'logoData', 'headerBannerData', 'managers', 'attendanceSignatures'))
            ->setPaper('a4', 'landscape')
            ->download('lista-recebimento-'.$paymentList->payment_date->format('Y-m-d').'.pdf');
    }

    public function payments(Event $event)
    {
        $this->authorize('view', $event);
        $event->load(['organization.users.roles', 'paymentLists.payments.participant', 'paymentLists.payments.signature']);
        [$settings, $logoData, $headerBannerData, $managers] = $this->documentOptions($event);
        $attendanceSignatures = $this->attendanceSignatures($event);

        return Pdf::loadView('exports.payments-all', compact('event', 'settings', 'logoData', 'headerBannerData', 'managers', 'attendanceSignatures'))
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
            : $organization->users->filter(fn ($user) => $user->roles->pluck('name')->intersect(['Administrador da Organização', 'Gestor de Eventos'])->isNotEmpty())->take(2);

        $logoData = null;
        if ($organization->logo_path && Storage::disk('local')->exists($organization->logo_path)) {
            $mime = Storage::disk('local')->mimeType($organization->logo_path) ?: 'image/png';
            $logoData = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($organization->logo_path));
        }

        $headerBannerData = null;
        $headerBannerPath = $settings['header_banner_path'] ?? null;
        if ($headerBannerPath && Storage::disk('local')->exists($headerBannerPath)) {
            $mime = Storage::disk('local')->mimeType($headerBannerPath) ?: 'image/png';
            $headerBannerData = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($headerBannerPath));
        }

        return [$settings, $logoData, $headerBannerData, $managers];
    }

    public static function signatureData(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(Storage::disk('local')->get($path));
    }
}
