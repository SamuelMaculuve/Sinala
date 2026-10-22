@extends('layouts.app',['title'=>($plan->exists?'Editar':'Novo').' pacote — Sinala'])
@section('content')
<a href="{{ route('admin.plans.index') }}" class="text-sm font-semibold text-stone-500 hover:text-orange-600">← Pacotes</a>
<p class="eyebrow mt-3">SUPER ADMINISTRAÇÃO</p><h1 class="mt-2 text-4xl font-bold">{{ $plan->exists?'Configurar '.$plan->name:'Criar pacote' }}</h1>
<form method="post" action="{{ $plan->exists?route('admin.plans.update',$plan):route('admin.plans.store') }}" class="mt-8 space-y-6">@csrf @if($plan->exists)@method('put')@endif
  <section class="grid gap-5 rounded-3xl bg-white p-6 sm:grid-cols-2 lg:p-8">
    <h2 class="text-lg font-bold sm:col-span-2">Identificação</h2>
    <label class="field">Nome<input name="name" required value="{{ old('name',$plan->name) }}"></label>
    <label class="field">Identificador (slug)<input name="slug" required value="{{ old('slug',$plan->slug) }}"></label>
    <label class="field sm:col-span-2">Descrição curta (visível para os clientes)<input name="description" maxlength="500" value="{{ old('description',$plan->description) }}" placeholder="Ex.: Ideal para projectos com formações mensais"></label>
  </section>
  <section class="grid gap-5 rounded-3xl bg-white p-6 sm:grid-cols-2 lg:grid-cols-3 lg:p-8">
    <h2 class="text-lg font-bold sm:col-span-2 lg:col-span-3">Pacote: eventos, pessoas e preço</h2>
    <label class="field">Número de eventos<input type="number" min="1" name="event_limit" required value="{{ old('event_limit',$plan->event_limit??10) }}"></label>
    <label class="field">Participantes por evento<input type="number" min="1" name="participant_limit" value="{{ old('participant_limit',$plan->participant_limit) }}" placeholder="Vazio = ilimitado"><small class="mt-1 block font-normal text-stone-400">Pessoas que cada evento pode registar e recolher assinaturas.</small></label>
    <label class="field">Preço (MT)<input type="number" min="0" name="price_mzn" required value="{{ old('price_mzn',$plan->price_mzn??0) }}"></label>
    <label class="field">Validade (dias)<input type="number" min="1" name="duration_days" value="{{ old('duration_days',$plan->duration_days) }}" placeholder="Vazio = sem expiração"><small class="mt-1 block font-normal text-stone-400">30 = mensal, 365 = anual.</small></label>
    <label class="field">Limite de utilizadores<input type="number" min="1" name="user_limit" required value="{{ old('user_limit',$plan->user_limit??3) }}"></label>
    <label class="field">Armazenamento (MB)<input type="number" min="1" name="storage_mb" required value="{{ old('storage_mb',$plan->storage_mb??500) }}"></label>
    <label class="field">Ordem de apresentação<input type="number" min="0" name="sort_order" value="{{ old('sort_order',$plan->sort_order??0) }}"></label>
    <label class="flex min-h-14 items-center gap-3 rounded-xl border p-4 sm:col-span-2"><input type="checkbox" name="monthly_event_limit" value="1" @checked(old('monthly_event_limit',$plan->monthly_event_limit))> Reiniciar contagem de eventos todos os meses (em vez de contar por pacote)</label>
  </section>
  <section class="grid gap-3 rounded-3xl bg-white p-6 sm:grid-cols-2 lg:p-8">
    <h2 class="text-lg font-bold sm:col-span-2">Disponibilidade</h2>
    <label class="flex min-h-14 items-center gap-3 rounded-xl border p-4"><input type="checkbox" name="active" value="1" @checked(old('active',$plan->exists?$plan->active:true))> Pacote activo</label>
    <label class="flex min-h-14 items-center gap-3 rounded-xl border p-4"><input type="checkbox" name="is_public" value="1" @checked(old('is_public',$plan->exists?$plan->is_public:true))> Visível para compra pelos clientes</label>
    <p class="text-sm text-stone-500 sm:col-span-2">Pacotes não visíveis podem ser atribuídos manualmente a uma organização (ex.: pacotes personalizados).</p>
  </section>
  <fieldset class="rounded-3xl bg-white p-6 lg:p-8"><legend class="sr-only">Funcionalidades</legend><h2 class="text-lg font-bold">Funcionalidades incluídas</h2><div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@foreach(\App\Models\Plan::FEATURES as $key=>$label)<label class="flex items-center gap-3 rounded-xl border p-4"><input type="checkbox" name="features[]" value="{{ $key }}" @checked(in_array($key,old('features',$plan->features??[])))> {{ $label }}</label>@endforeach</div></fieldset>
  <div class="flex flex-wrap gap-3"><a class="btn-secondary" href="{{ route('admin.plans.index') }}">Cancelar</a><button class="btn-primary">Guardar pacote</button></div>
</form>
@if($plan->exists && !$plan->subscriptions()->exists())<form method="post" action="{{ route('admin.plans.destroy',$plan) }}" class="mt-6" onsubmit="return confirm('Eliminar este pacote?')">@csrf @method('delete')<button class="text-sm font-semibold text-red-600">Eliminar pacote</button></form>@endif
@endsection
