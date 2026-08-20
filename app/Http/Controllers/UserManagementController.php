<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    private const ROLES = ['Administrador da Organização', 'Gestor de Eventos', 'Operador', 'Visualizador/Auditor', 'Coordenador Geral', 'Coordenadora de campo'];

    private const ADMIN_TIER_ROLES = ['Administrador da Organização', 'Coordenador Geral'];

    public function index(Request $request)
    {
        $this->authorizeSettings();
        $organization = $request->user()->organization->load('users.roles');
        $roles = self::ROLES;

        return view('organization.users', compact('organization', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeSettings();
        $organization = $request->user()->organization;
        abort_unless($user->organization_id === $organization->id, 404);

        $data = $request->validate(['role' => 'required|in:'.implode(',', self::ROLES)]);

        $wasAdminTier = $user->hasAnyRole(self::ADMIN_TIER_ROLES);
        if ($wasAdminTier && ! in_array($data['role'], self::ADMIN_TIER_ROLES, true)) {
            $remainingAdmins = $organization->users()
                ->where('id', '!=', $user->id)
                ->whereHas('roles', fn ($q) => $q->whereIn('name', self::ADMIN_TIER_ROLES))
                ->exists();
            abort_unless($remainingAdmins, 422, 'Tem de existir pelo menos um Administrador da Organização ou Coordenador Geral.');
        }

        $user->syncRoles([$data['role']]);

        return back()->with('success', 'Perfil de '.$user->name.' actualizado para '.$data['role'].'.');
    }

    private function authorizeSettings(): void
    {
        abort_unless(request()->user()->hasAnyRole(self::ADMIN_TIER_ROLES), 403);
    }
}
