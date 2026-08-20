@extends('layouts.app',['title'=>'Editar '.$participant->full_name.' — Sinala'])
@section('content')
<p class="eyebrow">PARTICIPANTES</p><h1 class="mt-2 text-4xl font-bold">Editar dados</h1>
<form method="post" action="{{ route('participants.update',$participant) }}" class="mt-8 grid max-w-3xl gap-5 rounded-3xl bg-white p-6 sm:grid-cols-2 lg:p-8">@csrf @method('put')
  <label class="field sm:col-span-2">Nome completo<input name="full_name" required value="{{ old('full_name',$participant->full_name) }}"></label>
  <label class="field">Sexo<select name="sex"><option value="">Seleccionar</option><option value="female" @selected(old('sex',$participant->sex)==='female')>Feminino</option><option value="male" @selected(old('sex',$participant->sex)==='male')>Masculino</option><option value="other" @selected(old('sex',$participant->sex)==='other')>Outro</option></select></label>
  <label class="field">Data de nascimento<input type="date" name="birth_date" value="{{ old('birth_date',optional($participant->birth_date)->format('Y-m-d')) }}"></label>
  <label class="field">Organização<input name="company" value="{{ old('company',$participant->company) }}"></label>
  <label class="field">Cargo<input name="position" value="{{ old('position',$participant->position) }}"></label>
  <label class="field">Província<input name="province" value="{{ old('province',$participant->province) }}"></label>
  <label class="field">Distrito<input name="district" value="{{ old('district',$participant->district) }}"></label>
  <label class="field">Telefone<input name="phone" value="{{ old('phone',$participant->phone) }}"></label>
  <label class="field">E-mail<input name="email" type="email" value="{{ old('email',$participant->email) }}"></label>
  <label class="field">Tipo de documento<input name="document_type" value="{{ old('document_type',$participant->document_type) }}"></label>
  <label class="field">Número do documento<input name="document_number" value="{{ old('document_number',$participant->document_number) }}"></label>
  <label class="field sm:col-span-2">Notas<textarea name="notes" rows="3">{{ old('notes',$participant->notes) }}</textarea></label>
  <div class="flex gap-3 sm:col-span-2"><a class="btn-secondary" href="{{ route('organization.participants') }}">Cancelar</a><button class="btn-primary">Guardar alterações</button></div>
</form>
@endsection
