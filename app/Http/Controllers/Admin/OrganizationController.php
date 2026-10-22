<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    public function index(Request $request, SubscriptionService $subscriptions)
    {
        $query = Organization::query()->with('subscription.plan')->withCount(['events', 'users', 'participants']);

        $query->when($request->filled('search'), function (Builder $q) use ($request) {
            $term = '%'.$request->string('search')->trim().'%';
            $q->where(fn (Builder $s) => $s->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('responsible_name', 'like', $term)->orWhere('nuit', 'like', $term));
        });
        $query->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')));
        $query->when($request->filled('plan'), fn (Builder $q) => $q->whereHas('subscription', fn (Builder $s) => $s->where('plan_id', $request->integer('plan'))));

        $organizations = $query->latest()->paginate(20)->withQueryString();
        $usages = $organizations->getCollection()->mapWithKeys(fn ($org) => [$org->id => $subscriptions->usage($org)]);
        $plans = Plan::orderBy('sort_order')->orderBy('price_mzn')->get(['id', 'name']);

        return view('admin.organizations.index', compact('organizations', 'usages', 'plans'));
    }

    public function show(Organization $organization, SubscriptionService $subscriptions)
    {
        $organization->load(['users.roles', 'subscriptions' => fn ($q) => $q->with('plan')->latest('id'), 'subscriptionPayments' => fn ($q) => $q->with('plan')->latest()->limit(20)])
            ->loadCount(['events', 'participants']);

        return view('admin.organizations.show', [
            'organization' => $organization,
            'usage' => $subscriptions->usage($organization),
            'plans' => Plan::orderBy('sort_order')->orderBy('price_mzn')->get(),
            'recentEvents' => $organization->events()->withCount('participants')->latest()->limit(10)->get(),
        ]);
    }

    public function update(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'name' => 'required|max:150',
            'email' => 'required|email|max:150',
            'responsible_name' => 'required|max:150',
            'phone' => 'nullable|max:30',
            'nuit' => 'nullable|max:30',
            'admin_notes' => 'nullable|max:3000',
        ]);
        $organization->update($data);

        return back()->with('success', 'Dados da organização actualizados.');
    }

    public function updateStatus(Request $request, Organization $organization)
    {
        $data = $request->validate(['status' => 'required|in:active,suspended', 'suspended_reason' => 'nullable|max:500']);
        $organization->update([
            'status' => $data['status'],
            'suspended_reason' => $data['status'] === 'suspended' ? ($data['suspended_reason'] ?? null) : null,
        ]);

        return back()->with('success', $data['status'] === 'suspended' ? 'Organização suspensa. Os utilizadores deixam de ter acesso.' : 'Organização reactivada.');
    }

    /** Start a new package (plan change), optionally free of charge, with custom limits. */
    public function assignPlan(Request $request, Organization $organization, SubscriptionService $subscriptions)
    {
        $data = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'expires_at' => 'nullable|date|after_or_equal:today',
            'event_limit' => 'nullable|integer|min:1',
            'participant_limit' => 'nullable|integer|min:1',
            'amount_mzn' => 'nullable|integer|min:0',
            'notes' => 'nullable|max:1000',
        ]);
        $plan = Plan::findOrFail($data['plan_id']);

        $attributes = array_filter([
            'event_limit' => $data['event_limit'] ?? null,
            'participant_limit' => $data['participant_limit'] ?? null,
            'notes' => $data['notes'] ?? null,
            'payment_method' => 'admin',
        ], fn ($v) => $v !== null);
        $attributes['amount_mzn'] = $data['amount_mzn'] ?? 0;
        if (array_key_exists('expires_at', $data) && $data['expires_at']) {
            $attributes['expires_at'] = $data['expires_at'];
        }

        $subscriptions->activate($organization, $plan, $attributes);

        return back()->with('success', 'Pacote '.$plan->name.' atribuído a '.$organization->name.'.');
    }

    /** Adjust the subscription in force without starting a new package. */
    public function updateSubscription(Request $request, Organization $organization, Subscription $subscription)
    {
        abort_unless($subscription->organization_id === $organization->id, 404);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Subscription::STATUSES))],
            'starts_at' => 'required|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'event_limit' => 'nullable|integer|min:1',
            'participant_limit' => 'nullable|integer|min:1',
            'notes' => 'nullable|max:1000',
        ]);
        $subscription->update($data + ['event_limit' => null, 'participant_limit' => null, 'expires_at' => null]);

        return back()->with('success', 'Subscrição actualizada.');
    }

    public function extend(Request $request, Organization $organization, Subscription $subscription)
    {
        abort_unless($subscription->organization_id === $organization->id, 404);
        $data = $request->validate(['days' => 'required|integer|min:1|max:3650']);
        $base = $subscription->expires_at && $subscription->expires_at->isFuture() ? $subscription->expires_at : today();
        $subscription->update(['expires_at' => $base->copy()->addDays($data['days']), 'status' => 'active']);

        return back()->with('success', 'Validade prolongada até '.$subscription->expires_at->format('d/m/Y').'.');
    }

    public function resetUserPassword(Organization $organization, User $user)
    {
        abort_unless($user->organization_id === $organization->id, 404);
        $password = Str::password(12, symbols: false);
        $user->update(['password' => Hash::make($password)]);

        return back()->with('success', 'Nova palavra-passe de '.$user->email.': '.$password.' — partilhe-a de forma segura.');
    }
}
