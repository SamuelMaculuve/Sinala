<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy {
    public function before(User $user): ?bool { return $user->is_super_admin ? true : null; }
    public function viewAny(User $user): bool { return (bool)$user->organization_id; }
    public function view(User $user,Event $event): bool { return $user->organization_id===$event->organization_id; }
    public function create(User $user): bool { return $user->hasAnyRole(['Administrador da Organização','Gestor de Eventos']); }
    public function update(User $user,Event $event): bool { return $this->view($user,$event)&&$user->hasAnyRole(['Administrador da Organização','Gestor de Eventos','Operador'])&&!$event->isClosed(); }
    public function editEventDetails(User $user,Event $event): bool { return $this->update($user,$event); }
    public function delete(User $user,Event $event): bool { return $this->view($user,$event)&&$user->hasAnyRole(['Administrador da Organização','Gestor de Eventos'])&&!$event->isClosed(); }
    public function close(User $user,Event $event): bool { return $this->view($user,$event)&&$user->hasAnyRole(['Administrador da Organização','Gestor de Eventos'])&&$event->hasEnded()&&!$event->isClosed(); }
    public function manageParticipants(User $user,Event $event): bool { return $this->update($user,$event); }
    public function recordAttendance(User $user,Event $event): bool { return $this->view($user,$event)&&$user->hasAnyRole(['Administrador da Organização','Gestor de Eventos','Operador'])&&!$event->isClosed(); }
    public function managePayments(User $user,Event $event): bool { return $this->recordAttendance($user,$event); }
}
