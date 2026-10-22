<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\BillingService;
use App\Services\Payments\MobilePaymentGateway;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    private const MANAGER_ROLES = ['Administrador da Organização', 'Coordenador Geral'];

    public function index(Request $request, SubscriptionService $subscriptions, MobilePaymentGateway $gateway)
    {
        $organization = $request->user()->organization;

        return view('organization.billing', [
            'organization' => $organization,
            'usage' => $subscriptions->usage($organization),
            'plans' => Plan::purchasable()->get(),
            'payments' => $organization->subscriptionPayments()->with('plan')->latest()->limit(15)->get(),
            'canPurchase' => $request->user()->hasAnyRole(self::MANAGER_ROLES),
            'gatewayReady' => $gateway->isConfigured(),
        ]);
    }

    public function checkout(Request $request, Plan $plan, BillingService $billing)
    {
        abort_unless($request->user()->hasAnyRole(self::MANAGER_ROLES), 403);
        abort_unless($plan->active && $plan->is_public, 404);
        abort_if($plan->isFree(), 422, 'Este pacote é gratuito e não requer pagamento.');

        $request->merge(['phone' => MobilePaymentGateway::normalizePhone($request->input('phone'))]);
        $request->validate(
            ['phone' => ['required', 'regex:'.MobilePaymentGateway::PHONE_PATTERN]],
            ['phone.regex' => 'Indique um número com 9 dígitos começado por 84 ou 85.']
        );

        // The API may wait for the customer to confirm on the phone.
        if (function_exists('set_time_limit')) {
            @set_time_limit(150);
        }

        $payment = $billing->purchase($request->user()->organization, $plan, $request->input('phone'), $request->user());

        return $payment->status === 'paid'
            ? redirect()->route('organization.billing')->with('success', 'Pagamento confirmado. O pacote '.$plan->name.' está activo.')
            : redirect()->route('organization.billing')->withErrors(['payment' => $payment->error_message ?: 'O pagamento não foi concluído.'])->withInput();
    }
}
