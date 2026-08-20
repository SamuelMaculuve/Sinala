<?php

namespace App\Http\Controllers;

use App\Models\{Event,Participant};
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ParticipantController extends Controller {
    public function store(Request $r,Event $event){$this->authorize('update',$event);$data=$r->validate(['full_name'=>'required|max:180','sex'=>'nullable|in:male,female,other','birth_date'=>'nullable|date','company'=>'nullable|max:180','position'=>'nullable|max:120','province'=>'nullable|max:100','district'=>'nullable|max:100','phone'=>'nullable|max:40','email'=>'nullable|email','document_number'=>'nullable|max:80','document_type'=>'nullable|max:80','notes'=>'nullable|max:1000']);$duplicate=$event->participants()->where(fn($q)=>$q->where('phone',$data['phone']??'')->orWhere('email',$data['email']??'__none__'))->exists();if($duplicate)return back()->withErrors(['participant'=>'Este participante já está inscrito no evento.']);$p=$r->user()->organization->participants()->create($data+['uuid'=>Str::uuid()]);$event->participants()->attach($p);return back()->with('success','Participante adicionado.');}
    public function destroy(Request $r,Event $event,Participant $participant){$this->authorize('update',$event);abort_unless($participant->organization_id===$r->user()->organization_id,404);$event->participants()->detach($participant);return back()->with('success','Participante removido do evento.');}

    public function edit(Request $r,Participant $participant)
    {
        $this->authorizeParticipant($r,$participant);

        return view('participants.edit',compact('participant'));
    }

    public function update(Request $r,Participant $participant)
    {
        $this->authorizeParticipant($r,$participant);
        $data=$r->validate(['full_name'=>'required|max:180','sex'=>'nullable|in:male,female,other','birth_date'=>'nullable|date','company'=>'nullable|max:180','position'=>'nullable|max:120','province'=>'nullable|max:100','district'=>'nullable|max:100','phone'=>'nullable|max:40','email'=>'nullable|email','document_number'=>'nullable|max:80','document_type'=>'nullable|max:80','notes'=>'nullable|max:1000']);
        $participant->update($data);

        return redirect()->route('organization.participants')->with('success','Dados de '.$participant->full_name.' actualizados.');
    }

    private function authorizeParticipant(Request $r,Participant $participant): void
    {
        abort_unless($participant->organization_id===$r->user()->organization_id,404);
        abort_unless($r->user()->hasAnyRole(['Administrador da Organização','Gestor de Eventos','Operador','Coordenador Geral','Coordenadora de campo']),403);
    }
}
