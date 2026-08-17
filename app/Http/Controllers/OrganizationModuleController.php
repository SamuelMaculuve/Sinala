<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\ParticipantPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationModuleController extends Controller
{
    public function participants(Request $request): View
    {
        $organization = $request->user()->organization;
        $query = $organization->participants()->withCount('events');

        $query->when($request->filled('event'), fn (Builder $q) => $q->whereHas('events', fn (Builder $events) => $events->whereKey($request->integer('event'))));
        $query->when($request->filled('search'), fn (Builder $q) => $q->where(fn (Builder $search) => $search
            ->where('full_name', 'like', '%'.$request->string('search')->trim().'%')
            ->orWhere('phone', 'like', '%'.$request->string('search')->trim().'%')
            ->orWhere('email', 'like', '%'.$request->string('search')->trim().'%')
            ->orWhere('company', 'like', '%'.$request->string('search')->trim().'%')));

        $sort = in_array($request->string('sort')->toString(), ['full_name', 'created_at'], true) ? $request->string('sort')->toString() : 'full_name';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
        $participants = $query->orderBy($sort, $direction)->paginate($this->perPage($request))->withQueryString();
        $events = $organization->events()->orderByDesc('starts_on')->get(['id', 'name']);

        return view('organization.participants', compact('participants', 'events'));
    }

    public function attendance(Request $request): View
    {
        $organization = $request->user()->organization;
        $query = AttendanceRecord::query()
            ->whereHas('event', fn (Builder $q) => $q->where('organization_id', $organization->id))
            ->with(['event:id,uuid,name', 'participant:id,full_name', 'signature']);

        $query->when($request->filled('event'), fn (Builder $q) => $q->where('event_id', $request->integer('event')));
        $query->when($request->filled('type'), fn (Builder $q) => $q->where('type', $request->string('type')));
        $query->when($request->filled('search'), fn (Builder $q) => $q->whereHas('participant', fn (Builder $participant) => $participant->where('full_name', 'like', '%'.$request->string('search')->trim().'%')));

        $records = $query->latest('recorded_at')->paginate($this->perPage($request))->withQueryString();
        $events = $organization->events()->orderByDesc('starts_on')->get(['id', 'name']);

        return view('organization.attendance', compact('records', 'events'));
    }

    public function payments(Request $request): View
    {
        $organization = $request->user()->organization;
        $query = ParticipantPayment::query()
            ->whereHas('paymentList.event', fn (Builder $q) => $q->where('organization_id', $organization->id))
            ->with(['participant:id,full_name', 'paymentList:id,uuid,event_id,name,currency', 'paymentList.event:id,uuid,name']);

        $query->when($request->filled('event'), fn (Builder $q) => $q->whereHas('paymentList', fn (Builder $list) => $list->where('event_id', $request->integer('event'))));
        $query->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')));
        $query->when($request->filled('search'), fn (Builder $q) => $q->whereHas('participant', fn (Builder $participant) => $participant->where('full_name', 'like', '%'.$request->string('search')->trim().'%')));

        $payments = $query->latest()->paginate($this->perPage($request))->withQueryString();
        $events = $organization->events()->orderByDesc('starts_on')->get(['id', 'name']);

        return view('organization.payments', compact('payments', 'events'));
    }

    public function reports(Request $request): View
    {
        $organization = $request->user()->organization;
        $query = $organization->events()
            ->withCount(['participants', 'attendanceRecords'])
            ->withSum(['paymentLists as total_paid' => fn ($q) => $q->join('participant_payments', 'payment_lists.id', '=', 'participant_payments.payment_list_id')->where('participant_payments.status', 'paid')], 'participant_payments.amount');

        $query->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')));
        $query->when($request->filled('search'), fn (Builder $q) => $q->where('name', 'like', '%'.$request->string('search')->trim().'%'));
        $events = $query->latest('starts_on')->paginate($this->perPage($request))->withQueryString();

        $summary = [
            'events' => $organization->events()->count(),
            'participants' => $organization->events()->withCount('participants')->get()->sum('participants_count'),
            'paid' => ParticipantPayment::where('status', 'paid')->whereHas('paymentList.event', fn (Builder $q) => $q->where('organization_id', $organization->id))->sum('amount'),
        ];

        return view('organization.reports', compact('events', 'summary'));
    }

    private function perPage(Request $request): int
    {
        return in_array($request->integer('per_page'), [10, 20, 50, 100], true) ? $request->integer('per_page') : 20;
    }
}
