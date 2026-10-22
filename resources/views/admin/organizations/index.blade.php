@extends('layouts.app',['title'=>'Organizações — Administração Sinala'])
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow">SUPER ADMINISTRAÇÃO</p><h1 class="mt-2 text-4xl font-bold">Organizações</h1><p class="mt-2 text-stone-500">Subscritores da plataforma, pacotes e utilização.</p></div><strong class="metric">{{ $organizations->total() }} encontradas</strong></div>
<form method="get" class="mt-7 grid gap-3 rounded-2xl border bg-white p-4 sm:grid-cols-2 xl:grid-cols-[minmax(240px,1fr)_200px_200px_auto]">
  <label class="field">Pesquisar<input name="search" value="{{ request('search') }}" placeholder="Nome, e-mail, responsável ou NUIT"></label>
  <label class="field">Estado<select name="status"><option value="">Todos</option><option value="active" @selected(request('status')==='active')>Activas</option><option value="suspended" @selected(request('status')==='suspended')>Suspensas</option></select></label>
  <label class="field">Pacote<select name="plan"><option value="">Todos</option>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected((string)$plan->id===request('plan'))>{{ $plan->name }}</option>@endforeach</select></label>
  <div class="flex items-end gap-2"><button class="btn-primary">Filtrar</button><a class="btn-secondary" href="{{ route('admin.organizations.index') }}">Limpar</a></div>
</form>
<section class="mt-5 overflow-hidden rounded-3xl bg-white"><div class="overflow-x-auto"><table class="w-full min-w-[980px] text-left text-sm"><caption class="sr-only">Organizações</caption><thead><tr><th>Organização</th><th>Pacote</th><th>Eventos usados</th><th>Part./evento</th><th>Validade</th><th>Utilizadores</th><th>Estado</th><th></th></tr></thead><tbody>
@forelse($organizations as $org)@php($usage=$usages[$org->id])<tr>
  <td><a class="font-semibold hover:text-orange-600" href="{{ route('admin.organizations.show',$org) }}">{{ $org->name }}</a><small class="block text-stone-400">{{ $org->email }}</small></td>
  <td>{{ $usage['plan']?->name ?? '—' }}@if($usage['subscription'] && !$usage['subscription']->isUsable())<small class="block text-red-600">{{ $usage['subscription']->statusLabel() }}</small>@endif</td>
  <td><div class="flex items-center gap-2"><div class="h-1.5 w-16 overflow-hidden rounded-full bg-stone-100"><i class="block h-full rounded-full {{ $usage['percentage']>=90?'bg-red-500':'bg-orange-500' }}" style="width:{{ $usage['percentage'] }}%"></i></div><small>{{ $usage['used'] }}/{{ $usage['limit'] }}</small></div></td>
  <td>{{ $usage['participant_limit'] ?? '∞' }}</td>
  <td>{{ $usage['expires_at']?->format('d/m/Y') ?? '—' }}</td>
  <td>{{ $org->users_count }}</td>
  <td><span class="status-pill status-{{ $org->isSuspended() ? 'suspended' : 'active' }}">{{ $org->isSuspended() ? 'Suspensa' : 'Activa' }}</span></td>
  <td class="text-right"><a class="btn-secondary min-h-10 px-4 text-sm" href="{{ route('admin.organizations.show',$org) }}">Gerir</a></td>
</tr>@empty<tr><td colspan="8" class="py-12 text-center text-stone-400">Nenhuma organização corresponde aos filtros.</td></tr>@endforelse
</tbody></table></div></section>
<div class="mt-6">{{ $organizations->links() }}</div>
@endsection
