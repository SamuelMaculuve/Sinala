<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\Payments\MobilePaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingService
{
    public function __construct(
        private readonly MobilePaymentGateway $gateway,
        private readonly SubscriptionService $subscriptions,
    ) {}

    /** Charge the phone and, if the API confirms, start the package straight away. */
    public function purchase(Organization $organization, Plan $plan, string $phone, ?User $user = null): SubscriptionPayment
    {
        $payment = $organization->subscriptionPayments()->create([
            'uuid' => (string) Str::uuid(),
            'plan_id' => $plan->id,
            'user_id' => $user?->id,
            'amount_mzn' => $plan->price_mzn,
            'phone' => MobilePaymentGateway::normalizePhone($phone),
            'status' => 'pending',
        ]);

        $result = $this->gateway->charge($payment->phone, $plan->price_mzn);

        $payment->forceFill([
            'provider_reference' => $result->reference,
            'provider_response' => $result->response,
            'error_message' => $result->successful ? null : $result->message,
        ])->save();

        return $result->successful
            ? $this->markPaid($payment)
            : tap($payment)->update(['status' => 'failed']);
    }

    /** Used after a successful charge, or by the platform admin to confirm a payment by hand. */
    public function markPaid(SubscriptionPayment $payment, ?User $confirmedBy = null): SubscriptionPayment
    {
        return DB::transaction(function () use ($payment, $confirmedBy) {
            $payment->refresh();
            if ($payment->status === 'paid') {
                return $payment;
            }

            $plan = $payment->plan ?? throw new \RuntimeException('Pagamento sem plano associado.');
            $subscription = $this->subscriptions->activate($payment->organization, $plan, [
                'amount_mzn' => $payment->amount_mzn,
                'payment_method' => $payment->method,
                'transaction_reference' => $payment->provider_reference,
            ]);

            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'subscription_id' => $subscription->id,
                'confirmed_by' => $confirmedBy?->id,
                'error_message' => null,
            ]);

            return $payment;
        });
    }
}
