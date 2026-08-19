@php($e = $event ?? null)
<label class="field sm:col-span-2">Nome do evento<input name="name" required value="{{ old('name', $e->name ?? '') }}"></label>
<label class="field">Tipo<select name="type" required>@foreach(['training'=>'Formação','workshop'=>'Workshop','seminar'=>'Seminário','conference'=>'Conferência','meeting'=>'Reunião','community'=>'Sessão comunitária','capacity'=>'Capacitação','other'=>'Outro'] as $v=>$l)<option value="{{ $v }}" @selected(old('type',$e->type ?? '')===$v)>{{ $l }}</option>@endforeach</select></label>
@if($e)<label class="field">Estado<select name="status" required>@foreach(['draft'=>'Rascunho','scheduled'=>'Agendado','ongoing'=>'Em curso','completed'=>'Concluído','cancelled'=>'Cancelado'] as $v=>$l)<option value="{{ $v }}" @selected(old('status',$e->status)===$v)>{{ $l }}</option>@endforeach</select></label>@endif
<label class="field">Local<input name="location" required value="{{ old('location', $e->location ?? '') }}"></label>
<label class="field">Província<input name="province" value="{{ old('province', $e->province ?? '') }}"></label>
<label class="field">Distrito<input name="district" value="{{ old('district', $e->district ?? '') }}"></label>
<label class="field">Data inicial<input type="date" name="starts_on" required value="{{ old('starts_on', optional($e?->starts_on)->format('Y-m-d')) }}"></label>
<label class="field">Data final<input type="date" name="ends_on" required value="{{ old('ends_on', optional($e?->ends_on)->format('Y-m-d')) }}"></label>
<label class="field">Hora inicial<input type="time" name="starts_at" value="{{ old('starts_at', $e->starts_at ?? '') }}"></label>
<label class="field">Hora final<input type="time" name="ends_at" value="{{ old('ends_at', $e->ends_at ?? '') }}"></label>
<label class="field">Facilitador<input name="facilitator" value="{{ old('facilitator', $e->facilitator ?? '') }}"></label>
<label class="field">Responsável<input name="responsible_name" value="{{ old('responsible_name', $e->responsible_name ?? '') }}"></label>
<label class="field">Contacto<input name="contact" value="{{ old('contact', $e->contact ?? '') }}"></label>
<label class="field">Participantes esperados<input type="number" min="0" name="expected_participants" value="{{ old('expected_participants', $e->expected_participants ?? '') }}"></label>
<label class="field sm:col-span-2">Descrição<textarea name="description" rows="4">{{ old('description', $e->description ?? '') }}</textarea></label>
<label class="flex items-center gap-3 sm:col-span-2"><input type="checkbox" name="requires_check_out" value="1" @checked(old('requires_check_out', $e->requires_check_out ?? false))> Exigir assinatura na saída</label>
