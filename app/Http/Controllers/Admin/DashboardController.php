<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $latestSubscriptions = Subscription::whereIn('id', Subscription::selectRaw('max(id)')->groupBy('organization_id'))->with('plan', 'organization')->get();
        $expiringSoon = $latestSubscriptions
            ->filter(fn ($s) => $s->organization && $s->expires_at && $s->isUsable() && $s->expires_at->lte(today()->addDays(14)))
            ->sortBy('expires_at')
            ->values();

        $stats = [
            'organizations' => Organization::count(),
            'suspended' => Organization::where('status', 'suspended')->count(),
            'active_subscriptions' => $latestSubscriptions->filter->isUsable()->count(),
            'paid_subscriptions' => $latestSubscriptions->filter(fn ($s) => $s->isUsable() && $s->plan && ! $s->plan->isFree())->count(),
            'revenue_month' => SubscriptionPayment::where('status', 'paid')->where('paid_at', '>=', now()->startOfMonth())->sum('amount_mzn'),
            'revenue_total' => SubscriptionPayment::where('status', 'paid')->sum('amount_mzn'),
            'pending_payments' => SubscriptionPayment::where('status', 'pending')->count(),
            'events' => Event::count(),
            'participants' => Participant::count(),
        ];

        $planBreakdown = $latestSubscriptions->filter->isUsable()->groupBy(fn ($s) => $s->plan?->name ?? '—')->map->count()->sortDesc();
        $recentPayments = SubscriptionPayment::with('organization', 'plan')->latest()->limit(8)->get();
        $recentOrganizations = Organization::with('subscription.plan')->latest()->limit(6)->get();

        return view('admin.dashboard', compact('stats', 'expiringSoon', 'planBreakdown', 'recentPayments', 'recentOrganizations'));
    }
}
