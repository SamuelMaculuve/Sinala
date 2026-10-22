<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function current(Organization $organization): ?Subscription
    {
        return $organization->subscription()->with('plan')->first();
    }

    public function usage(Organization $organization): array
    {
        $subscription = $this->current($organization);
        if (! $subscription) {
            return ['used' => 0, 'limit' => 0, 'remaining' => 0, 'percentage' => 100, 'can_create' => false, 'plan' => null, 'subscription' => null, 'participant_limit' => 0, 'expires_at' => null, 'active' => false, 'period' => 'sem período'];
        }

        $plan = $subscription->plan;
        $used = $this->eventsUsed($organization, $subscription);
        $limit = $subscription->effectiveEventLimit();
        $active = $subscription->isUsable() && ! $organization->isSuspended();

        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => max(0, $limit - $used),
            'percentage' => $limit ? min(100, (int) round($used / $limit * 100)) : 100,
            'can_create' => $active && $used < $limit,
            'plan' => $plan,
            'subscription' => $subscription,
            'participant_limit' => $subscription->effectiveParticipantLimit(),
            'expires_at' => $subscription->expires_at,
            'active' => $active,
            'period' => $plan?->monthly_event_limit ? 'neste mês' : 'neste pacote',
        ];
    }

    /**
     * Events counted against the package: this month for monthly plans,
     * otherwise everything created since the package started.
     */
    public function eventsUsed(Organization $organization, Subscription $subscription): int
    {
        $query = $organization->events();
        if ($subscription->plan?->monthly_event_limit) {
            $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
        } elseif ($subscription->starts_at) {
            $query->where('created_at', '>=', $subscription->starts_at->copy()->startOfDay());
        }

        return $query->count();
    }

    public function canCreateEvent(Organization $organization): bool
    {
        return $this->usage($organization)['can_create'];
    }

    /** Participants allowed per event for this organization. Null means unlimited. */
    public function participantLimit(Organization $organization): ?int
    {
        return $this->current($organization)?->effectiveParticipantLimit();
    }

    /** How many more participants this event can take. Null means unlimited. */
    public function remainingParticipants(Event $event): ?int
    {
        $limit = $this->participantLimit($event->organization);
        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $event->participants()->count());
    }

    public function canAddParticipants(Event $event, int $count = 1): bool
    {
        $remaining = $this->remainingParticipants($event);

        return $remaining === null || $remaining >= $count;
    }

    /**
     * Start a new package for the organization. Older usable subscriptions are closed
     * so the latest one is always the one in force.
     */
    public function activate(Organization $organization, Plan $plan, array $attributes = []): Subscription
    {
        return DB::transaction(function () use ($organization, $plan, $attributes) {
            $organization->subscriptions()
                ->whereIn('status', ['active', 'trial', 'pending'])
                ->update(['status' => 'expired', 'updated_at' => now()]);

            return $organization->subscriptions()->create($attributes + [
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => today(),
                'expires_at' => $plan->duration_days ? today()->addDays($plan->duration_days) : null,
                'amount_mzn' => $plan->price_mzn,
            ]);
        });
    }
}
