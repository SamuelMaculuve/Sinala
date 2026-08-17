@extends('layouts.app',['title'=>'Participantes — Sinala'])
@section('content')
<div class="flex items-end justify-between gap-4"><div><p class="eyebrow">PARTICIPANTES</p><h1 class="mt-2 text-4xl font-bold">Pessoas registadas</h1><p class="mt-2 text-stone-500">Participantes pertencentes à sua organização.</p></div><strong class="metric">{{ $participants->total() }} no total</strong></div>
<section class="mt-8 overflow-hidden rounded-3xl bg-white"><div class="overflow-x-auto"><table class="w-full min-w-[720px] text-left"><thead><tr><th>Nome</th><th>Contacto</th><th>Organização</th><th>Eventos</th></tr></thead><tbody>@forelse($participants as $participant)<tr><td class="font-semibold">{{ $participant->full_name }}</td><td>{{ $participant->phone ?: $participant->email ?: '—' }}</td><td>{{ $participant->company ?: '—' }}</td><td>{{ $participant->events_count }}</td></tr>@empty<tr><td colspan="4" class="py-12 text-center text-stone-400">Ainda não existem participantes.</td></tr>@endforelse</tbody></table></div></section>
<div class="mt-6">{{ $participants->links() }}</div>
@endsection
