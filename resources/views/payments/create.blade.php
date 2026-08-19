@extends('layouts.app',['title'=>'Nova lista — '.$event->name.' — Sinala'])
@section('content')
<div class="mx-auto max-w-4xl">
  <a class="text-sm font-semibold text-orange-600" href="{{ route('events.show',$event) }}">← {{ $event->name }}</a>
  <p class="eyebrow mt-5">PAGAMENTOS / SUBSÍDIOS</p><h1 class="mt-2 text-4xl font-bold">Marcar presenças e criar lista</h1><p class="mt-2 text-stone-500">Seleccione quem esteve presente. Quem já tem entrada assinada vem pré-marcado.</p>

  @if($errors->any())<div class="mt-6 rounded-2xl bg-red-50 p-5 text-sm text-red-700">{{ $errors->first() }}</div>@endif

  <form method="post" action="{{ route('payments.lists.store',$event) }}" class="mt-8 space-y-6">@csrf
    <section class="rounded-3xl border border-stone-200 bg-white p-6">
      <div class="flex flex-wrap items-end justify-between gap-3"><h2 class="text-xl font-bold">Participantes ({{ $participants->count() }})</h2>
        <div class="flex flex-wrap items-end gap-2"><label class="field" style="min-width:220px">Pesquisar<input id="participant-search" placeholder="Nome do participante"></label><button type="button" id="select-all" class="btn-secondary">Seleccionar todos</button><button type="button" id="select-none" class="btn-secondary">Desmarcar todos</button></div>
      </div>
      <div class="mt-5 max-h-[420px] overflow-auto rounded-2xl border">
        <table class="w-full text-left"><caption class="sr-only">Participantes do evento</caption><thead><tr class="bg-stone-50"><th class="w-10"></th><th>Nome</th><th>Organização</th><th>Presença</th></tr></thead>
          <tbody>@forelse($participants as $p)<tr data-participant-row data-name="{{ mb_strtolower($p->full_name) }}"><td><input type="checkbox" name="participant_ids[]" value="{{ $p->id }}" @checked($presentIds->contains($p->id))></td><td>{{ $p->full_name }}</td><td>{{ $p->company ?: '—' }}</td><td>@if($presentIds->contains($p->id))<span class="badge-success">Presença já registada</span>@endif</td></tr>@empty<tr><td colspan="4" class="py-8 text-center text-stone-400">Este evento ainda não tem participantes.</td></tr>@endforelse</tbody>
        </table>
      </div>
    </section>

    <section class="rounded-3xl bg-stone-950 p-6 text-white">
      <h2 class="text-xl font-bold">Dados da lista</h2>
      <div class="mt-5 grid gap-4 sm:grid-cols-2">
        <label class="field dark">Nome<input name="name" value="Subsídio de transporte" required></label>
        <label class="field dark">Tipo<input name="type" value="Transporte" required></label>
        <label class="field dark">Valor padrão<input name="default_amount" type="number" step="0.01" required></label>
        <label class="field dark">Moeda<select name="currency"><option>MZN</option><option>USD</option><option>EUR</option><option>ZAR</option></select></label>
        <label class="field dark">Data<input name="payment_date" type="date" required value="{{ today()->format('Y-m-d') }}"></label>
        <label class="field dark">Centro de custo<input name="cost_center"></label>
      </div>
      <div class="mt-5 flex gap-3"><a class="btn-secondary border-stone-600 text-white" href="{{ route('events.show',$event) }}">Cancelar</a><button class="btn-primary flex-1">Criar lista com os presentes seleccionados</button></div>
    </section>
  </form>
</div>
@push('scripts')<script>
const search=document.querySelector('#participant-search'),rows=document.querySelectorAll('[data-participant-row]');
search?.addEventListener('input',()=>{const q=search.value.toLowerCase();rows.forEach(row=>row.hidden=!row.dataset.name.includes(q))});
document.querySelector('#select-all')?.addEventListener('click',()=>rows.forEach(row=>{if(!row.hidden)row.querySelector('input[type=checkbox]').checked=true}));
document.querySelector('#select-none')?.addEventListener('click',()=>rows.forEach(row=>{if(!row.hidden)row.querySelector('input[type=checkbox]').checked=false}));
</script>@endpush
@endsection
