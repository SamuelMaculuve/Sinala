<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DocumentSettingsController extends Controller
{
    public function edit()
    {
        $organization = request()->user()->organization->load('users.roles');
        $this->authorizeSettings();

        return view('organization.document-settings', compact('organization'));
    }

    public function update(Request $request)
    {
        $this->authorizeSettings();
        $organization = $request->user()->organization;
        $data = $request->validate([
            'header_title' => 'nullable|string|max:180',
            'header_subtitle' => 'nullable|string|max:220',
            'project_name' => 'nullable|string|max:220',
            'funding_reference' => 'nullable|string|max:220',
            'footer_note' => 'nullable|string|max:500',
            'signatory_user_ids' => 'nullable|array|max:2',
            'signatory_user_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('organization_id', $organization->id)],
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'secondary_logos' => 'nullable|array|max:3',
            'secondary_logos.*' => 'image|mimes:png,jpg,jpeg|max:2048',
            'header_banner' => 'nullable|image|mimes:png,jpg,jpeg|max:4096',
            'remove_logo' => 'nullable|boolean',
            'remove_secondary_logos' => 'nullable|array|max:3',
            'remove_secondary_logos.*' => 'integer|min:0|max:2|distinct',
            'remove_header_banner' => 'nullable|boolean',
        ]);

        $settings = $organization->report_settings ?? [];
        $secondaryLogoPaths = array_values(array_filter(
            $settings['secondary_logo_paths'] ?? [],
            fn ($path) => is_string($path) && $path !== ''
        ));
        $removeSecondaryIndexes = collect($data['remove_secondary_logos'] ?? [])
            ->map(fn ($index) => (int) $index)
            ->unique()
            ->sortDesc();
        $newSecondaryLogos = $request->file('secondary_logos', []);
        $removedSecondaryCount = $removeSecondaryIndexes
            ->filter(fn ($index) => isset($secondaryLogoPaths[$index]))
            ->count();

        if (count($secondaryLogoPaths) - $removedSecondaryCount + count($newSecondaryLogos) > 3) {
            throw ValidationException::withMessages([
                'secondary_logos' => 'Pode configurar no máximo três logótipos adicionais.',
            ]);
        }

        if ($request->hasFile('logo')) {
            if ($organization->logo_path) {
                Storage::disk('local')->delete($organization->logo_path);
            }
            $organization->logo_path = $request->file('logo')->store('organization-logos', 'local');
        } elseif ($request->boolean('remove_logo') && $organization->logo_path) {
            Storage::disk('local')->delete($organization->logo_path);
            $organization->logo_path = null;
        }

        foreach ($removeSecondaryIndexes as $index) {
            if (isset($secondaryLogoPaths[$index])) {
                Storage::disk('local')->delete($secondaryLogoPaths[$index]);
                unset($secondaryLogoPaths[$index]);
            }
        }

        $secondaryLogoPaths = array_values($secondaryLogoPaths);
        foreach ($newSecondaryLogos as $logo) {
            $secondaryLogoPaths[] = $logo->store('organization-logos/secondary', 'local');
        }
        $settings['secondary_logo_paths'] = $secondaryLogoPaths;

        if ($request->hasFile('header_banner')) {
            if (! empty($settings['header_banner_path'])) {
                Storage::disk('local')->delete($settings['header_banner_path']);
            }
            $settings['header_banner_path'] = $request->file('header_banner')->store('organization-headers', 'local');
        } elseif ($request->boolean('remove_header_banner') && ! empty($settings['header_banner_path'])) {
            Storage::disk('local')->delete($settings['header_banner_path']);
            $settings['header_banner_path'] = null;
        }

        unset($data['logo'], $data['secondary_logos'], $data['header_banner'], $data['remove_logo'], $data['remove_secondary_logos'], $data['remove_header_banner']);
        $organization->report_settings = array_merge($settings, $data);
        $organization->save();

        return back()->with('success', 'Cabeçalho e responsáveis dos documentos actualizados.');
    }

    public function logo()
    {
        $organization = request()->user()->organization;
        abort_unless($organization->logo_path && Storage::disk('local')->exists($organization->logo_path), 404);

        return Storage::disk('local')->response($organization->logo_path, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    public function headerBanner()
    {
        $path = request()->user()->organization->report_settings['header_banner_path'] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    public function secondaryLogo(int $index)
    {
        $paths = request()->user()->organization->report_settings['secondary_logo_paths'] ?? [];
        $path = $paths[$index] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    private function authorizeSettings(): void
    {
        abort_unless(request()->user()->hasAnyRole(['Administrador da Organização', 'Coordenador Geral']), 403);
    }
}
