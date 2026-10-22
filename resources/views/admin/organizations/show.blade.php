@extends('layouts.app',['title'=>$organization->name.' — Administração Sinala'])
@section('content')
@php($subscription=$usage['subscription'])
<a href="{{ route('admin.organizations.index') }}" class="text-sm font-semibold text-stone-500 hover:text-orange-600">← Organizações</a>
<div class="mt-3 flex flex-wrap items-start justify-between gap-4">
  <div><p class="eyebrow">ORGANIZAÇÃO</p><h1 class="mt-2 text-4xl font-bold">{{ $organization->name }}</h1><p class="mt-2 text-stone-500">{{ $organization->responsible_name }} · {{ $organization->email }}{{ $organization->phone ? ' · '.$organization->phone : '' }} · Registada em {{ $organization->created_at->format('d/m/Y') }}</p></div>
  <span class="status-pill status-{{ $organization->isSuspended() ? 'suspended' : 'active' }} text-sm">{{ $organization->isSuspended() ? 'Suspensa' : 'Activa' }}</span>
</div>
@if($organization->isSuspended())<div class="mt-5 rounded-2xl bg-red-50 p-5 text-red-800">Conta suspensa{{ $organization->suspended_reason ? ': '.$organization->suspended_reason : '' }}. Os utilizadores não conseguem aceder.</div>@endif

<section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
  <article class="metric"><small>Pacote</small><strong>{{ $usage['plan']?->name ?? 'Sem pacote' }}</strong>@if($subscription)<span class="mt-1 block text-xs text-stone-400">{{ $subscription->statusLabel() }}</span>@endif</article>
  <article class="metric"><small>Eventos {{ $usage['period'] }}</small><strong>{{ $usage['used'] }} / {{ $usage['limit'] }}</strong></article>
  <article class="metric"><small>Participantes por evento</small><strong>{{ $usage['participant_limit'] ?? 'Ilimitado' }}</strong></article>
  <article class="metric"><small>Validade</small><strong>{{ $usage['expires_at']?->format('d/m/Y') ?? 'Sem expiração' }}</strong></article>
  <article class="metric"><small>Totais</small><strong>{{ $organization->events_count }} eventos</strong><span class="mt-1 block text-xs text-stone-400">{{ $organization->participants_count }} participantes</span></article>
</section>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
  {{-- New package --}}
  <form method="post" action="{{ route('admin.organizations.assign-plan',$organization) }}" class="rounded-3xl bg-white p-6">@csrf
    <h2 class="text-xl font-bold">Atribuir novo pacote</h2><p class="mt-1 text-sm text-stone-500">Inicia um novo período hoje e fecha o pacote actual. Use para upgrades, ofertas ou pagamentos recebidos fora da plataforma.</p>
    <div class="mt-5 grid gap-4 sm:grid-cols-2">
      <label class="field sm:col-span-2">Pacote<select name="plan_id" required>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected($usage['plan']?->id===$plan->id)>{{ $plan->name }} — {{ $plan->event_limit }} eventos, {{ $plan->participant_limit ?? '∞' }} part./evento, {{ number_format($plan->price_mzn,0,',','.') }} MT{{ $plan->active ? '' : ' (inactivo)' }}</option>@endforeach</select></label>
      <label class="field">Eventos (personalizado)<input type="number" min="1" name="event_limit" placeholder="Valor do pacote"></label>
      <label class="field">Participantes por evento<input type="number" min="1" name="participant_limit" placeholder="Valor do pacote"></label>
      <label class="field">Válido até<input type="date" name="expires_at"><small class="mt-1 block font-normal text-stone-400">Vazio = duração do pacote</small></label>
      <label class="field">Valor recebido (MT)<input type="number" min="0" name="amount_mzn" value="0"></label>
      <label class="field sm:col-span-2">Notas<input name="notes" placeholder="Ex.: pago por transferência, ref. 1234"></label>
    </div>
    <button class="btn-primary mt-5">Atribuir pacote</button>
  </form>

  {{-- Adjust current subscription --}}
  @if($subscription)
  <div class="space-y-6">
    <form method="post" action="{{ route('admin.organizations.subscriptions.update',[$organization,$subscription]) }}" class="rounded-3xl bg-white p-6">@csrf @method('put')
      <h2 class="text-xl font-bold">Ajustar subscrição actual</h2><p class="mt-1 text-sm text-stone-500">Altera limites e datas sem criar um novo período. Deixe os limites vazios para usar os valores do pacote ({{ $subscription->plan?->event_limit }} eventos, {{ $subscription->plan?->participant_limit ?? '∞' }} part./evento).</p>
      <div class="mt-5 grid gap-4 sm:grid-cols-2">
        <label class="field">Estado<select name="status">@foreach(\App\Models\Subscription::STATUSES as $value=>$label)<option value="{{ $value }}" @selected($subscription->status===$value)>{{ $label }}</option>@endforeach</select></label>
        <label class="field">Início<input type="date" name="starts_at" required value="{{ $subscription->starts_at?->format('Y-m-d') }}"></label>
        <label class="field">Eventos<input type="number" min="1" name="event_limit" value="{{ $subscription->event_limit }}" placeholder="{{ $subscription->plan?->event_limit }}"></label>
        <label class="field">Participantes por evento<input type="number" min="1" name="participant_limit" value="{{ $subscription->participant_limit }}" placeholder="{{ $subscription->plan?->participant_limit ?? 'Ilimitado' }}"></label>
        <label class="field">Válido até<input type="date" name="expires_at" value="{{ $subscription->expires_at?->format('Y-m-d') }}"></label>
        <label class="field">Notas<input name="notes" value="{{ $subscription->notes }}"></label>
      </div>
      <button class="btn-primary mt-5">Guardar ajustes</button>
    </form>
    <form method="post" action="{{ route('admin.organizations.subscriptions.extend',[$organization,$subscription]) }}" class="flex flex-wrap items-end gap-3 rounded-3xl bg-white p-6">@csrf
      <label class="field flex-1">Prolongar validade<select name="days"><option value="7">7 dias</option><option value="30" selected>30 dias</option><option value="90">90 dias</option><option value="365">1 ano</option></select></label>
      <button class="btn-secondary">Prolongar</button>
    </form>
  </div>
  @endif
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
  <form method="post" action="{{ route('admin.organizations.update',$organization) }}" class="rounded-3xl bg-white p-6">@csrf @method('put')
    <h2 class="text-xl font-bold">Dados da organização</h2>
    <div class="mt-5 grid gap-4 sm:grid-cols-2">
      <label class="field sm:col-span-2">Nome<input name="name" required value="{{ $organization->name }}"></label>
      <label class="field">Responsável<input name="responsible_name" required value="{{ $organization->responsible_name }}"></label>
      <label class="field">E-mail<input type="email" name="email" required value="{{ $organization->email }}"></label>
      <label class="field">Telefone<input name="phone" value="{{ $organization->phone }}"></label>
      <label class="field">NUIT<input name="nuit" value="{{ $organization->nuit }}"></label>
      <label class="field sm:col-span-2">Notas internas (só visíveis para administradores)<textarea name="admin_notes" rows="3">{{ $organization->admin_notes }}</textarea></label>
    </div>
    <button class="btn-primary mt-5">Guardar dados</button>
  </form>

  <div class="space-y-6">
    <form method="post" action="{{ route('admin.organizations.status',$organization) }}" class="rounded-3xl bg-white p-6" onsubmit="return confirm('Confirmar alteração do acesso desta organização?')">@csrf @method('put')
      <h2 class="text-xl font-bold">Acesso à plataforma</h2>
      @if($organization->isSuspended())
        <input type="hidden" name="status" value="active"><p class="mt-2 text-sm text-stone-500">Reactive para devolver o acesso a todos os utilizadores.</p><button class="btn-primary mt-4">Reactivar organização</button>
      @else
        <input type="hidden" name="status" value="suspended"><p class="mt-2 text-sm text-stone-500">Bloqueia o acesso de todos os utilizadores. Os dados são mantidos.</p><label class="field mt-4">Motivo (mostrado aos utilizadores)<input name="suspended_reason" placeholder="Ex.: pagamento em atraso"></label><button class="btn-secondary mt-4 text-red-600">Suspender organização</button>
      @endif
    </form>
    <section class="overflow-hidden rounded-3xl bg-white"><h2 class="p-6 pb-0 text-xl font-bold">Utilizadores</h2><div class="mt-4 overflow-x-auto"><table class="w-full min-w-[520px] text-left text-sm"><thead><tr><th>Nome</th><th>Perfil</th><th></th></tr></thead><tbody>
      @foreach($organization->users as $user)<tr><td><strong>{{ $user->name }}</strong><small class="block text-stone-400">{{ $user->email }}</small></td><td>{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td><td class="text-right"><form method="post" action="{{ route('admin.organizations.users.reset-password',[$organization,$user]) }}" onsubmit="return confirm('Gerar nova palavra-passe para {{ $user->email }}?')">@csrf<button class="btn-secondary min-h-10 px-3 text-xs">Nova palavra-passe</button></form></td></tr>@endforeach
    </tbody></table></div></section>
  </div>
</div>

<section class="mt-6 overflow-hidden rounded-3xl bg-white"><h2 class="p-6 pb-0 text-xl font-bold">Histórico de subscrições</h2><div class="mt-4 overflow-x-auto"><table class="w-full min-w-[760px] text-left text-sm"><thead><tr><th>Pacote</th><th>Estado</th><th>Período</th><th>Limites</th><th>Valor</th><th>Origem</th><th>Notas</th></tr></thead><tbody>
  @foreach($organization->subscriptions as $item)<tr><td class="font-semibold">{{ $item->plan?->name }}</td><td><span class="status-pill status-{{ $item->isUsable() ? 'active' : 'expired' }}">{{ $item->statusLabel() }}</span></td><td>{{ $item->starts_at?->format('d/m/Y') }} — {{ $item->expires_at?->format('d/m/Y') ?? '∞' }}</td><td>{{ $item->effectiveEventLimit() }} eventos · {{ $item->effectiveParticipantLimit() ?? '∞' }} part.</td><td>{{ number_format($item->amount_mzn,0,',','.') }} MT</td><td>{{ $item->payment_method ?? '—' }}</td><td class="text-stone-500">{{ $item->notes }}</td></tr>@endforeach
</tbody></table></div></section>

<section class="mt-6 overflow-hidden rounded-3xl bg-white"><h2 class="p-6 pb-0 text-xl font-bold">Pagamentos</h2><div class="mt-4 overflow-x-auto"><table class="w-full min-w-[700px] text-left text-sm"><thead><tr><th>Data</th><th>Pacote</th><th>Valor</th><th>Número</th><th>Estado</th><th>Referência</th></tr></thead><tbody>
  @forelse($organization->subscriptionPayments as $payment)<tr><td>{{ $payment->created_at->format('d/m/Y H:i') }}</td><td>{{ $payment->plan?->name }}</td><td>{{ number_format($payment->amount_mzn,0,',','.') }} MT</td><td>{{ $payment->phone }}</td><td><span class="status-pill status-{{ $payment->status }}">{{ $payment->statusLabel() }}</span></td><td>{{ $payment->provider_reference ?? '—' }}</td></tr>
  @empty<tr><td colspan="6" class="py-8 text-center text-stone-400">Sem pagamentos.</td></tr>@endforelse
</tbody></table></div></section>

<section class="mt-6 overflow-hidden rounded-3xl bg-white"><h2 class="p-6 pb-0 text-xl font-bold">Eventos recentes</h2><div class="mt-4 overflow-x-auto"><table class="w-full min-w-[600px] text-left text-sm"><thead><tr><th>Evento</th><th>Data</th><th>Participantes</th><th>Criado</th></tr></thead><tbody>
  @forelse($recentEvents as $event)<tr><td class="font-semibold">{{ $event->name }}</td><td>{{ $event->starts_on->format('d/m/Y') }}</td><td>{{ $event->participants_count }}{{ $usage['participant_limit'] ? ' / '.$usage['participant_limit'] : '' }}</td><td>{{ $event->created_at->format('d/m/Y') }}</td></tr>
  @empty<tr><td colspan="4" class="py-8 text-center text-stone-400">Sem eventos.</td></tr>@endforelse
</tbody></table></div></section>
@endsection
