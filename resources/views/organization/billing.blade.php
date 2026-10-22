@extends('layouts.app',['title'=>'Subscrição — Sinala'])
@section('content')
@php($subscription=$usage['subscription'])
<div class="flex flex-wrap items-end justify-between gap-4">
  <div><p class="eyebrow">CONTA</p><h1 class="mt-2 text-4xl font-bold">Subscrição</h1><p class="mt-2 max-w-2xl text-stone-500">O seu pacote define quantos eventos pode criar e quantas pessoas cada evento pode registar e recolher assinaturas.</p></div>
</div>

<section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
  <article class="metric"><small>Pacote actual</small><strong>{{ $usage['plan']?->name ?? 'Sem pacote' }}</strong>@if($subscription)<span class="status-pill status-{{ $usage['active'] ? 'active' : 'expired' }} mt-2">{{ $usage['active'] ? 'Activo' : $subscription->statusLabel() }}</span>@endif</article>
  <article class="metric"><small>Eventos {{ $usage['period'] }}</small><strong>{{ $usage['used'] }} / {{ $usage['limit'] }}</strong><div class="mt-3 h-2 overflow-hidden rounded-full bg-stone-100"><div class="h-full rounded-full {{ $usage['percentage']>=90?'bg-red-500':'bg-orange-500' }}" style="width:{{ $usage['percentage'] }}%"></div></div></article>
  <article class="metric"><small>Participantes por evento</small><strong>{{ $usage['participant_limit'] ?? 'Ilimitado' }}</strong></article>
  <article class="metric"><small>Válido até</small><strong>{{ $usage['expires_at']?->format('d/m/Y') ?? 'Sem expiração' }}</strong></article>
</section>

<section class="mt-10">
  <h2 class="text-2xl font-bold">Pacotes disponíveis</h2>
  @unless($gatewayReady)<p class="mt-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">O pagamento por telemóvel ainda não está disponível. Contacte o suporte da Sinala para activar um pacote.</p>@endunless
  <div class="mt-5 grid gap-5 lg:grid-cols-3">
    @forelse($plans as $plan)
      @php($isCurrent=$usage['plan']?->id===$plan->id && $usage['active'])
      <article class="flex flex-col rounded-3xl border bg-white p-6 {{ $isCurrent ? 'border-orange-400 ring-2 ring-orange-100' : 'border-stone-200' }}">
        <div class="flex items-center justify-between"><h3 class="text-xl font-bold">{{ $plan->name }}</h3>@if($isCurrent)<span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-bold text-orange-700">Actual</span>@endif</div>
        @if($plan->description)<p class="mt-2 text-sm text-stone-500">{{ $plan->description }}</p>@endif
        <p class="mt-4 text-3xl font-black">{{ $plan->isFree() ? 'Grátis' : number_format($plan->price_mzn,0,',','.').' MT' }}@unless($plan->isFree())<small class="text-sm font-medium text-stone-500"> / {{ $plan->durationLabel() }}</small>@endunless</p>
        <ul class="mt-5 space-y-2 text-sm">
          <li>✓ <strong>{{ $plan->event_limit }}</strong> {{ Str::plural('evento',$plan->event_limit) }} {{ $plan->monthly_event_limit ? 'por mês' : 'no pacote' }}</li>
          <li>✓ <strong>{{ $plan->participant_limit ?? 'Participantes ilimitados' }}</strong>{{ $plan->participant_limit ? ' participantes por evento' : '' }}</li>
          <li>✓ <strong>{{ $plan->user_limit }}</strong> {{ Str::plural('utilizador',$plan->user_limit) }}</li>
          @foreach(collect($plan->features ?? [])->take(5) as $feature)<li class="text-stone-500">· {{ \App\Models\Plan::FEATURES[$feature] ?? $feature }}</li>@endforeach
        </ul>
        <div class="mt-auto pt-6">
          @if($plan->isFree())
            <p class="text-sm text-stone-400">Pacote de entrada, sem pagamento.</p>
          @elseif(!$canPurchase)
            <p class="text-sm text-stone-400">Apenas o administrador da organização pode comprar pacotes.</p>
          @elseif($gatewayReady)
            <form method="post" action="{{ route('organization.billing.checkout',$plan) }}" class="space-y-3" data-checkout>@csrf
              <label class="field">Número M-Pesa<input name="phone" inputmode="numeric" maxlength="13" pattern="(\+?258)?\s*8[45][0-9\s]{7,9}" placeholder="84 ou 85 + 7 dígitos" value="{{ old('phone') }}" required></label>
              <button class="btn-primary w-full">{{ $isCurrent ? 'Renovar' : 'Comprar' }} por {{ number_format($plan->price_mzn,0,',','.') }} MT</button>
              <p class="text-xs text-stone-400">Vai receber um pedido de confirmação no telemóvel. Mantenha esta página aberta.</p>
            </form>
          @endif
        </div>
      </article>
    @empty
      <p class="rounded-2xl bg-white p-6 text-stone-500">Não existem pacotes disponíveis de momento.</p>
    @endforelse
  </div>
</section>

<section class="mt-10 overflow-hidden rounded-3xl bg-white">
  <h2 class="p-6 pb-0 text-2xl font-bold">Histórico de pagamentos</h2>
  <div class="mt-4 overflow-x-auto"><table class="w-full min-w-[640px] text-left"><thead><tr><th>Data</th><th>Pacote</th><th>Valor</th><th>Número</th><th>Estado</th><th>Referência</th></tr></thead><tbody>
    @forelse($payments as $payment)<tr><td>{{ $payment->created_at->format('d/m/Y H:i') }}</td><td>{{ $payment->plan?->name ?? '—' }}</td><td>{{ number_format($payment->amount_mzn,0,',','.') }} MT</td><td>{{ \App\Support\Mask::phone($payment->phone) }}</td><td><span class="status-pill status-{{ $payment->status }}">{{ $payment->statusLabel() }}</span>@if($payment->status==='failed' && $payment->error_message)<small class="mt-1 block text-xs text-stone-400">{{ $payment->error_message }}</small>@endif</td><td class="text-sm text-stone-500">{{ $payment->provider_reference ?? '—' }}</td></tr>
    @empty<tr><td colspan="6" class="py-10 text-center text-stone-400">Ainda não existem pagamentos.</td></tr>@endforelse
  </tbody></table></div>
</section>
@push('scripts')<script>document.querySelectorAll('[data-checkout]').forEach(f=>f.addEventListener('submit',()=>{const b=f.querySelector('button');b.disabled=true;b.textContent='A aguardar confirmação no telemóvel…';}));</script>@endpush
@endsection
