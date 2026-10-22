@extends('layouts.app',['title'=>'Visão geral — Administração Sinala'])
@section('content')
<div class="mx-auto max-w-[1500px]">
<div><p class="eyebrow">SUPER ADMINISTRAÇÃO</p><h1 class="mt-2 text-4xl font-bold">Visão geral da plataforma</h1></div>

<section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
  <a href="{{ route('admin.organizations.index') }}" class="metric hover:border-orange-300"><small>Organizações</small><strong>{{ $stats['organizations'] }}</strong><span class="mt-1 block text-xs text-stone-400">{{ $stats['suspended'] }} suspensas</span></a>
  <article class="metric"><small>Subscrições activas</small><strong>{{ $stats['active_subscriptions'] }}</strong><span class="mt-1 block text-xs text-stone-400">{{ $stats['paid_subscriptions'] }} pagas</span></article>
  <a href="{{ route('admin.payments.index',['status'=>'paid']) }}" class="metric hover:border-orange-300"><small>Receita este mês</small><strong>{{ number_format($stats['revenue_month'],0,',','.') }} MT</strong><span class="mt-1 block text-xs text-stone-400">{{ number_format($stats['revenue_total'],0,',','.') }} MT no total</span></a>
  <a href="{{ route('admin.payments.index',['status'=>'pending']) }}" class="metric hover:border-orange-300"><small>Pagamentos pendentes</small><strong class="{{ $stats['pending_payments'] ? 'text-amber-600' : '' }}">{{ $stats['pending_payments'] }}</strong><span class="mt-1 block text-xs text-stone-400">{{ $stats['events'] }} eventos · {{ $stats['participants'] }} participantes</span></a>
</section>

<div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
  <div class="space-y-6">
    <section class="overflow-hidden rounded-3xl bg-white">
      <div class="flex items-center justify-between p-6 pb-0"><h2 class="text-xl font-bold">Pagamentos recentes</h2><a class="text-sm font-semibold text-orange-600" href="{{ route('admin.payments.index') }}">Ver todos →</a></div>
      <div class="mt-4 overflow-x-auto"><table class="w-full min-w-[600px] text-left text-sm"><thead><tr><th>Organização</th><th>Pacote</th><th>Valor</th><th>Estado</th><th>Data</th></tr></thead><tbody>
        @forelse($recentPayments as $payment)<tr><td>@if($payment->organization)<a class="font-semibold hover:text-orange-600" href="{{ route('admin.organizations.show',$payment->organization) }}">{{ $payment->organization->name }}</a>@else<span class="text-stone-400">Removida</span>@endif</td><td>{{ $payment->plan?->name ?? '—' }}</td><td>{{ number_format($payment->amount_mzn,0,',','.') }} MT</td><td><span class="status-pill status-{{ $payment->status }}">{{ $payment->statusLabel() }}</span></td><td>{{ $payment->created_at->format('d/m/Y H:i') }}</td></tr>
        @empty<tr><td colspan="5" class="py-10 text-center text-stone-400">Ainda não existem pagamentos.</td></tr>@endforelse
      </tbody></table></div>
    </section>
    <section class="overflow-hidden rounded-3xl bg-white">
      <div class="flex items-center justify-between p-6 pb-0"><h2 class="text-xl font-bold">Novas organizações</h2><a class="text-sm font-semibold text-orange-600" href="{{ route('admin.organizations.index') }}">Ver todas →</a></div>
      <div class="mt-4 overflow-x-auto"><table class="w-full min-w-[600px] text-left text-sm"><thead><tr><th>Organização</th><th>Responsável</th><th>Pacote</th><th>Registo</th></tr></thead><tbody>
        @foreach($recentOrganizations as $org)<tr><td><a class="font-semibold hover:text-orange-600" href="{{ route('admin.organizations.show',$org) }}">{{ $org->name }}</a></td><td>{{ $org->responsible_name }}</td><td>{{ $org->subscription?->plan?->name ?? '—' }}</td><td>{{ $org->created_at->format('d/m/Y') }}</td></tr>@endforeach
      </tbody></table></div>
    </section>
  </div>
  <aside class="space-y-6">
    <section class="rounded-3xl bg-white p-6"><h2 class="text-xl font-bold">A expirar em 14 dias</h2><div class="mt-4 space-y-3">
      @forelse($expiringSoon as $subscription)<a href="{{ route('admin.organizations.show',$subscription->organization) }}" class="flex items-center justify-between rounded-xl border p-3 hover:border-orange-300"><span><strong class="block text-sm">{{ $subscription->organization->name }}</strong><small class="text-stone-400">{{ $subscription->plan?->name }}</small></span><span class="text-sm font-semibold text-amber-700">{{ $subscription->expires_at->format('d/m') }}</span></a>
      @empty<p class="text-sm text-stone-400">Nenhuma subscrição a expirar.</p>@endforelse
    </div></section>
    <section class="rounded-3xl bg-white p-6"><h2 class="text-xl font-bold">Organizações por pacote</h2><dl class="mt-4 space-y-2 text-sm">
      @forelse($planBreakdown as $name=>$count)<div class="flex justify-between"><dt>{{ $name }}</dt><dd class="font-bold">{{ $count }}</dd></div>@empty<p class="text-stone-400">Sem dados.</p>@endforelse
    </dl><a class="btn-secondary mt-5 w-full" href="{{ route('admin.plans.index') }}">Gerir pacotes</a></section>
  </aside>
</div>
</div>
@endsection
