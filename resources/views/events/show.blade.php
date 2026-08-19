@extends('layouts.app',['title'=>$event->name.' — Sinala'])
@section('content')
<div class="flex flex-wrap items-start justify-between gap-5">
  <div><span class="badge-success">{{ ['draft'=>'Rascunho','scheduled'=>'Agendado','ongoing'=>'Em curso','completed'=>'Concluído','cancelled'=>'Cancelado'][$event->status] ?? $event->status }}</span>@if($event->isClosed())<span class="status-pill status-closed ml-2">Fechado</span>@endif<h1 class="mt-3 text-4xl font-bold">{{ $event->name }}</h1><p class="mt-2 text-stone-500">{{ $event->location }} · {{ $event->starts_on->format('d/m/Y') }} — {{ $event->ends_on->format('d/m/Y') }}</p>@if($syncCandidatesCount)<p class="mt-3 text-sm font-medium text-amber-700">{{ $syncCandidatesCount }} participante(s) têm presença gravada mas continuam como pendente. Use “Corrigir estados”.</p>@endif</div>
  <div class="flex flex-wrap gap-3">@can('editEventDetails',$event)<a class="btn-secondary" href="{{ route('events.edit',$event) }}">Editar evento</a>@endcan
@can('manageParticipants',$event)<form method="post" action="{{ route('events.sync-attendance-statuses',$event) }}">@csrf<button class="btn-secondary">Corrigir estados{{ $syncCandidatesCount ? ' ('.$syncCandidatesCount.')' : '' }}</button></form>@endcan
<a class="btn-secondary" href="{{ route('exports.attendance',$event) }}">↓ Exportar presença PDF</a>@if($event->paymentLists->count())<a class="btn-secondary" href="{{ route('exports.payments',$event) }}">↓ Baixar todas as listas (PDF)</a>@endif
@can('managePayments',$event)<a class="btn-primary" href="{{ route('attendance.kiosk',$event) }}">Modo de assinatura</a>@endcan
@can('close',$event)<form method="post" action="{{ route('events.close',$event) }}" onsubmit="return confirm('Fechar este evento? Deixará de ser possível editar dados, presenças, pagamentos ou participantes.')">@csrf<button class="btn-primary bg-stone-900">Fechar evento</button></form>@endcan</div>
</div>
@if($event->isClosed())<div class="mt-5 rounded-2xl bg-stone-900 p-5 text-white">Evento fechado em {{ $event->closed_at->format('d/m/Y H:i') }}{{ $event->closedBy ? ' por '.$event->closedBy->name : '' }}. Os dados continuam disponíveis apenas para consulta, relatórios e exportação.</div>@endif

<div class="mt-8 grid gap-6 xl:grid-cols-[1fr_360px]">
  <div class="space-y-6">
    <section class="rounded-3xl bg-white p-6">
      <div class="flex justify-between"><h2 class="text-2xl font-bold">Participantes</h2><strong>{{ $event->participants_count }}</strong></div>
      <div class="mt-5 overflow-auto">
        <table class="w-full min-w-[760px] text-left">
          <caption class="sr-only">Participantes do evento</caption>
          <thead><tr><th>Nome</th><th>Organização</th><th>Telefone</th><th>Estado</th><th class="text-right">Assinatura</th></tr></thead>
          <tbody>
            @foreach($participants as $p)
              @php
                $signedRecords = $participantAttendances->get($p->id, collect());
                $signatureOptions = $signedRecords->map(fn ($record) => [
                    'url' => route('attendance.signature', $record),
                    'label' => ($record->type === 'check_out' ? 'Saída' : 'Entrada').' · '.$record->recorded_at->format('d/m/Y H:i'),
                ])->values();
              @endphp
              <tr>
                <td>{{ $p->full_name }}</td>
                <td>{{ $p->company ?: '—' }}</td>
                <td>{{ $p->phone ?: '—' }}</td>
                <td><span @class(['inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold',$p->pivot->status === 'present' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'])>{{ $p->pivot->status === 'present' ? 'Presente' : 'Pendente' }}</span></td>
                <td class="text-right">
                  @if($signedRecords->isNotEmpty())
                    <button type="button" class="btn-secondary min-h-10 whitespace-nowrap px-4 text-sm" data-preview-signatures data-participant-name="{{ $p->full_name }}" data-signatures='@json($signatureOptions)'>Ver assinatura{{ $signedRecords->count() > 1 ? 's ('.$signedRecords->count().')' : '' }}</button>
                  @else
                    <span class="text-sm text-stone-400">Sem assinatura</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="mt-5">{{ $participants->links() }}</div>
    </section>

    <section class="rounded-3xl bg-white p-6"><div class="flex items-center justify-between"><h2 class="text-2xl font-bold">Listas de pagamento</h2><strong>{{ $event->paymentLists->count() }}</strong></div><p class="mt-1 text-sm text-stone-500">Geradas a partir dos participantes marcados como presentes. O recebimento exige assinatura.</p><div class="mt-5 space-y-3">@forelse($event->paymentLists as $list)<div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border p-4"><div><strong>{{ $list->name }}</strong><p class="mt-1 text-sm text-stone-500">{{ $list->type }} · {{ number_format($list->default_amount,2,',','.') }} {{ $list->currency }}</p></div><div class="flex flex-wrap gap-2"><a class="btn-primary" href="{{ route('payments.lists.show',$list) }}">Abrir e recolher assinaturas</a><a class="btn-secondary" href="{{ route('exports.payment',$list) }}">↓ PDF</a></div></div>@empty<p class="rounded-2xl bg-stone-50 p-5 text-sm text-stone-500">Ainda não existem listas de pagamento neste evento.</p>@endforelse</div></section>
  </div>

  <aside class="space-y-6">
    @can('manageParticipants',$event)<form method="post" action="{{ route('participants.store',$event) }}" class="rounded-3xl bg-white p-6">@csrf<h2 class="text-xl font-bold">Adicionar participante</h2><div class="mt-5 space-y-4"><label class="field">Nome completo<input name="full_name" required></label><label class="field">Telefone<input name="phone"></label><label class="field">E-mail<input name="email" type="email"></label><label class="field">Organização<input name="company"></label><label class="field">Sexo<select name="sex"><option value="">Seleccionar</option><option value="female">Feminino</option><option value="male">Masculino</option><option value="other">Outro</option></select></label><button class="btn-primary w-full">Adicionar</button></div></form>@endcan
    @can('managePayments',$event)<div class="rounded-3xl bg-stone-950 p-6 text-white"><h2 class="text-xl font-bold">Nova lista de pagamento</h2><p class="mt-2 text-sm leading-6 text-stone-400">Escolha quem esteve presente antes de gerar a lista. Todas começam como pendentes.</p><a class="btn-primary mt-5 block w-full text-center" href="{{ route('payments.lists.create',$event) }}">Marcar presenças e criar lista</a></div>@endcan
  </aside>
</div>

<dialog id="event-signature-preview" class="w-full max-w-3xl rounded-3xl border-0 p-0 shadow-2xl backdrop:bg-stone-950/70">
  <div class="bg-white">
    <div class="flex items-center justify-between gap-4 border-b border-stone-200 px-5 py-4 sm:px-6">
      <div><p class="text-xs font-black tracking-[.18em] text-orange-600">ASSINATURA</p><h2 id="event-signature-title" class="mt-1 text-xl font-bold text-stone-950">Pré-visualização</h2></div>
      <button type="button" class="btn-secondary min-h-10 px-4 text-sm" data-close-event-signature>Fechar</button>
    </div>
    <div id="event-signature-options" class="flex flex-wrap gap-2 border-b border-stone-200 px-5 py-3 sm:px-6"></div>
    <div class="bg-stone-100 p-4 sm:p-6"><img id="event-signature-image" alt="Assinatura do participante" class="mx-auto max-h-[65vh] w-auto max-w-full rounded-2xl bg-white p-3 shadow-sm"></div>
  </div>
</dialog>

<script>
  const eventSignatureDialog = document.querySelector('#event-signature-preview');
  const eventSignatureImage = document.querySelector('#event-signature-image');
  const eventSignatureTitle = document.querySelector('#event-signature-title');
  const eventSignatureOptions = document.querySelector('#event-signature-options');

  function selectEventSignature(option, buttons, activeButton) {
    eventSignatureImage.src = option.url;
    buttons.forEach(button => button.className = 'rounded-full bg-stone-100 px-3 py-2 text-xs font-semibold text-stone-600');
    activeButton.className = 'rounded-full bg-orange-100 px-3 py-2 text-xs font-semibold text-orange-800';
  }

  document.querySelectorAll('[data-preview-signatures]').forEach(button => button.addEventListener('click', () => {
    const signatures = JSON.parse(button.dataset.signatures);
    const optionButtons = [];
    eventSignatureTitle.textContent = `Assinatura de ${button.dataset.participantName}`;
    eventSignatureOptions.replaceChildren();

    signatures.forEach((signature, index) => {
      const optionButton = document.createElement('button');
      optionButton.type = 'button';
      optionButton.textContent = signature.label;
      optionButton.className = 'rounded-full bg-stone-100 px-3 py-2 text-xs font-semibold text-stone-600';
      optionButton.addEventListener('click', () => selectEventSignature(signature, optionButtons, optionButton));
      optionButtons.push(optionButton);
      eventSignatureOptions.appendChild(optionButton);
      if (index === 0) selectEventSignature(signature, optionButtons, optionButton);
    });

    eventSignatureDialog.showModal();
  }));

  document.querySelector('[data-close-event-signature]')?.addEventListener('click', () => eventSignatureDialog.close());
  eventSignatureDialog?.addEventListener('click', event => {
    if (event.target === eventSignatureDialog) eventSignatureDialog.close();
  });
</script>
@endsection
