<?php
namespace App\Services;
use App\Models\Organization;
use Carbon\Carbon;

class SubscriptionService {
    public function usage(Organization $organization): array {
        $subscription=$organization->subscription()->with('plan')->first();
        if(!$subscription) return ['used'=>0,'limit'=>0,'remaining'=>0,'percentage'=>100,'can_create'=>false,'plan'=>null,'period'=>'sem período'];
        $plan=$subscription->plan;
        $query=$organization->events();
        if($plan->monthly_event_limit) $query->whereBetween('created_at',[now()->startOfMonth(),now()->endOfMonth()]);
        $used=$query->count(); $limit=$plan->event_limit;
        return ['used'=>$used,'limit'=>$limit,'remaining'=>max(0,$limit-$used),'percentage'=>$limit ? min(100,(int) round($used/$limit*100)) : 100,'can_create'=>$subscription->status === 'active' && (!$subscription->expires_at || $subscription->expires_at->isFuture()) && $used<$limit,'plan'=>$plan,'period'=>$plan->monthly_event_limit?'neste mês':'no total'];
    }
    public function canCreateEvent(Organization $organization): bool { return $this->usage($organization)['can_create']; }
}
