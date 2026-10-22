@extends('layouts.app',['title'=>'Pacotes — Administração Sinala'])
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow">SUPER ADMINISTRAÇÃO</p><h1 class="mt-2 text-4xl font-bold">Pacotes de subscrição</h1><p class="mt-2 text-stone-500">Defina quantos eventos e quantas pessoas por evento cada pacote inclui, o preço e a validade.</p></div><a class="btn-primary" href="{{ route('admin.plans.create') }}">+ Novo pacote</a></div>
<div class="mt-8 grid gap-5 lg:grid-cols-3">@foreach($plans as $plan)<article class="flex flex-col rounded-3xl border bg-white p-6">
  <div class="flex flex-wrap items-center gap-2"><span class="{{ $plan->active?'badge-success':'rounded-full bg-stone-200 px-3 py-1 text-xs' }}">{{ $plan->active?'Activo':'Inactivo' }}</span>@unless($plan->is_public)<span class="rounded-full bg-stone-100 px-3 py-1 text-xs">Oculto</span>@endunless<span class="ml-auto text-sm text-stone-400">{{ $plan->slug }}</span></div>
  <h2 class="mt-6 text-2xl font-bold">{{ $plan->name }}</h2>@if($plan->description)<p class="mt-1 text-sm text-stone-500">{{ $plan->description }}</p>@endif
  <p class="mt-2 text-3xl font-black">{{ number_format($plan->price_mzn,0,',','.') }} <small class="text-sm font-medium text-stone-500">MT / {{ $plan->durationLabel() }}</small></p>
  <dl class="mt-6 space-y-3 text-sm">
    <div class="flex justify-between"><dt>Eventos</dt><dd class="font-bold">{{ $plan->event_limit }} {{ $plan->monthly_event_limit?'por mês':'por pacote' }}</dd></div>
    <div class="flex justify-between"><dt>Participantes por evento</dt><dd class="font-bold">{{ $plan->participant_limit ?? 'Ilimitado' }}</dd></div>
    <div class="flex justify-between"><dt>Utilizadores</dt><dd class="font-bold">{{ $plan->user_limit }}</dd></div>
    <div class="flex justify-between"><dt>Armazenamento</dt><dd class="font-bold">{{ $plan->storage_mb>=1024?number_format($plan->storage_mb/1024,0).' GB':$plan->storage_mb.' MB' }}</dd></div>
    <div class="flex justify-between"><dt>Funcionalidades</dt><dd class="font-bold">{{ count($plan->features??[]) }}</dd></div>
    <div class="flex justify-between"><dt>Subscrições activas</dt><dd class="font-bold">{{ $plan->active_subscriptions_count }}</dd></div>
  </dl>
  <div class="mt-auto pt-7"><a href="{{ route('admin.plans.edit',$plan) }}" class="btn-secondary w-full">Configurar pacote</a></div>
</article>@endforeach</div>
@endsection
