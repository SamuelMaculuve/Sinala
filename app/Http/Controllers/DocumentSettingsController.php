<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
            'header_banner' => 'nullable|image|mimes:png,jpg,jpeg|max:4096',
            'remove_logo' => 'nullable|boolean',
            'remove_header_banner' => 'nullable|boolean',
        ]);

        if ($request->hasFile('logo')) {
            if ($organization->logo_path) {
                Storage::disk('local')->delete($organization->logo_path);
            }
            $organization->logo_path = $request->file('logo')->store('organization-logos', 'local');
        } elseif ($request->boolean('remove_logo') && $organization->logo_path) {
            Storage::disk('local')->delete($organization->logo_path);
            $organization->logo_path = null;
        }

        $settings = $organization->report_settings ?? [];
        if ($request->hasFile('header_banner')) {
            if (! empty($settings['header_banner_path'])) {
                Storage::disk('local')->delete($settings['header_banner_path']);
            }
            $settings['header_banner_path'] = $request->file('header_banner')->store('organization-headers', 'local');
        } elseif ($request->boolean('remove_header_banner') && ! empty($settings['header_banner_path'])) {
            Storage::disk('local')->delete($settings['header_banner_path']);
            $settings['header_banner_path'] = null;
        }

        unset($data['logo'], $data['header_banner'], $data['remove_logo'], $data['remove_header_banner']);
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

    private function authorizeSettings(): void
    {
        abort_unless(request()->user()->hasAnyRole(['Administrador da Organização', 'Coordenador Geral']), 403);
    }
}
