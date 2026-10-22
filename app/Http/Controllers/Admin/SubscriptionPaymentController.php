<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Services\BillingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SubscriptionPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = SubscriptionPayment::query()->with(['organization', 'plan', 'user', 'confirmedBy']);
        $query->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')));
        $query->when($request->filled('search'), function (Builder $q) use ($request) {
            $term = '%'.$request->string('search')->trim().'%';
            $q->where(fn (Builder $s) => $s->where('phone', 'like', $term)->orWhere('provider_reference', 'like', $term)->orWhereHas('organization', fn (Builder $o) => $o->where('name', 'like', $term)));
        });

        $payments = $query->latest()->paginate(25)->withQueryString();
        $totals = [
            'paid' => SubscriptionPayment::where('status', 'paid')->sum('amount_mzn'),
            'pending' => SubscriptionPayment::where('status', 'pending')->count(),
            'failed' => SubscriptionPayment::where('status', 'failed')->count(),
        ];

        return view('admin.payments.index', compact('payments', 'totals'));
    }

    public function confirm(Request $request, SubscriptionPayment $payment, BillingService $billing)
    {
        abort_if($payment->status === 'paid', 422, 'Este pagamento já foi confirmado.');
        $billing->markPaid($payment, $request->user());

        return back()->with('success', 'Pagamento confirmado e pacote activado para '.$payment->organization->name.'.');
    }

    public function reject(SubscriptionPayment $payment)
    {
        abort_if($payment->status === 'paid', 422, 'Não é possível rejeitar um pagamento confirmado.');
        $payment->update(['status' => 'failed', 'error_message' => $payment->error_message ?: 'Rejeitado pelo administrador.']);

        return back()->with('success', 'Pagamento marcado como falhado.');
    }
}
