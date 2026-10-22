@extends('layouts.app',['title'=>'Pagamentos — Administração Sinala'])
@section('content')
<div><p class="eyebrow">SUPER ADMINISTRAÇÃO</p><h1 class="mt-2 text-4xl font-bold">Pagamentos de subscrição</h1><p class="mt-2 text-stone-500">Pagamentos por telemóvel feitos pelas organizações. Confirme manualmente quando o dinheiro foi recebido mas o pedido ficou pendente.</p></div>
<section class="mt-7 grid gap-4 sm:grid-cols-3"><article class="metric"><small>Total recebido</small><strong>{{ number_format($totals['paid'],0,',','.') }} MT</strong></article><a href="{{ route('admin.payments.index',['status'=>'pending']) }}" class="metric"><small>Pendentes</small><strong class="{{ $totals['pending'] ? 'text-amber-600' : '' }}">{{ $totals['pending'] }}</strong></a><a href="{{ route('admin.payments.index',['status'=>'failed']) }}" class="metric"><small>Falhados</small><strong>{{ $totals['failed'] }}</strong></a></section>
<form method="get" class="mt-6 grid gap-3 rounded-2xl border bg-white p-4 sm:grid-cols-[1fr_200px_auto]">
  <label class="field">Pesquisar<input name="search" value="{{ request('search') }}" placeholder="Organização, número ou referência"></label>
  <label class="field">Estado<select name="status"><option value="">Todos</option>@foreach(\App\Models\SubscriptionPayment::STATUSES as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select></label>
  <div class="flex items-end gap-2"><button class="btn-primary">Filtrar</button><a class="btn-secondary" href="{{ route('admin.payments.index') }}">Limpar</a></div>
</form>
<section class="mt-5 overflow-hidden rounded-3xl bg-white"><div class="overflow-x-auto"><table class="w-full min-w-[1000px] text-left text-sm"><thead><tr><th>Data</th><th>Organização</th><th>Pacote</th><th>Valor</th><th>Número</th><th>Estado</th><th>Referência / erro</th><th></th></tr></thead><tbody>
@forelse($payments as $payment)<tr>
  <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
  <td>@if($payment->organization)<a class="font-semibold hover:text-orange-600" href="{{ route('admin.organizations.show',$payment->organization) }}">{{ $payment->organization->name }}</a>@else<span class="text-stone-400">Removida</span>@endif<small class="block text-stone-400">{{ $payment->user?->name }}</small></td>
  <td>{{ $payment->plan?->name ?? '—' }}</td>
  <td class="whitespace-nowrap">{{ number_format($payment->amount_mzn,0,',','.') }} MT</td>
  <td>{{ $payment->phone }}</td>
  <td><span class="status-pill status-{{ $payment->status }}">{{ $payment->statusLabel() }}</span>@if($payment->confirmedBy)<small class="mt-1 block text-xs text-stone-400">Confirmado por {{ $payment->confirmedBy->name }}</small>@endif</td>
  <td class="max-w-[240px] text-stone-500">{{ $payment->provider_reference ?? '' }}@if($payment->error_message)<small class="block text-red-600">{{ $payment->error_message }}</small>@endif</td>
  <td class="text-right">@if($payment->status!=='paid' && $payment->organization && $payment->plan)<div class="flex justify-end gap-2"><form method="post" action="{{ route('admin.payments.confirm',$payment) }}" onsubmit="return confirm('Confirmar o pagamento e activar o pacote?')">@csrf<button class="btn-primary min-h-10 px-3 text-xs">Confirmar</button></form>@if($payment->status==='pending')<form method="post" action="{{ route('admin.payments.reject',$payment) }}">@csrf<button class="btn-secondary min-h-10 px-3 text-xs">Rejeitar</button></form>@endif</div>@endif</td>
</tr>@empty<tr><td colspan="8" class="py-12 text-center text-stone-400">Nenhum pagamento encontrado.</td></tr>@endforelse
</tbody></table></div></section>
<div class="mt-6">{{ $payments->links() }}</div>
@endsection
