@extends('layouts.app',['title'=>'Participantes — Sinala'])
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow">PARTICIPANTES</p><h1 class="mt-2 text-4xl font-bold">Pessoas registadas</h1><p class="mt-2 text-stone-500">Pesquisa e paginação processadas no servidor.</p></div><strong class="metric">{{ $participants->total() }} encontrados</strong></div>
<form method="get" class="mt-7 grid gap-3 rounded-2xl border bg-white p-4 sm:grid-cols-2 xl:grid-cols-[minmax(240px,1fr)_minmax(220px,1fr)_160px_120px_auto]">
  <label class="field">Pesquisar<input name="search" value="{{ request('search') }}" placeholder="Nome, contacto ou organização"></label>
  <label class="field">Evento<select name="event"><option value="">Todos os eventos</option>@foreach($events as $event)<option value="{{ $event->id }}" @selected((string)$event->id===request('event'))>{{ $event->name }}</option>@endforeach</select></label>
  <label class="field">Ordenar<select name="sort"><option value="full_name" @selected(request('sort','full_name')==='full_name')>Nome</option><option value="created_at" @selected(request('sort')==='created_at')>Data de registo</option></select></label>
  <label class="field">Por página<select name="per_page">@foreach([10,20,50,100] as $size)<option @selected((int)request('per_page',20)===$size)>{{ $size }}</option>@endforeach</select></label>
  <div class="flex items-end gap-2"><button class="btn-primary">Filtrar</button><a class="btn-secondary" href="{{ route('organization.participants') }}">Limpar</a></div>
</form>
<section class="mt-5 overflow-hidden rounded-3xl bg-white"><div class="overflow-x-auto"><table class="w-full min-w-[720px] text-left"><caption class="sr-only">Participantes filtrados</caption><thead><tr><th>Nome</th><th>Contacto</th><th>Organização</th><th>Eventos</th></tr></thead><tbody>@forelse($participants as $participant)<tr><td class="font-semibold">{{ $participant->full_name }}</td><td>{{ $participant->phone ?: $participant->email ?: '—' }}</td><td>{{ $participant->company ?: '—' }}</td><td>{{ $participant->events_count }}</td></tr>@empty<tr><td colspan="4" class="py-12 text-center text-stone-400">Nenhum participante corresponde aos filtros.</td></tr>@endforelse</tbody></table></div></section>
<div class="mt-6">{{ $participants->links() }}</div>
@endsection
