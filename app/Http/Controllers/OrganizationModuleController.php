<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\ParticipantPayment;
use Illuminate\View\View;

class OrganizationModuleController extends Controller
{
    public function participants(): View
    {
        $organization = request()->user()->organization;
        $participants = $organization->participants()
            ->withCount('events')
            ->orderBy('full_name')
            ->paginate(20);

        return view('organization.participants', compact('participants'));
    }

    public function attendance(): View
    {
        $organizationId = request()->user()->organization_id;
        $records = AttendanceRecord::query()
            ->whereHas('event', fn ($query) => $query->where('organization_id', $organizationId))
            ->with(['event:id,uuid,name', 'participant:id,full_name', 'signature'])
            ->latest('recorded_at')
            ->paginate(20);

        return view('organization.attendance', compact('records'));
    }

    public function payments(): View
    {
        $organizationId = request()->user()->organization_id;
        $payments = ParticipantPayment::query()
            ->whereHas('paymentList.event', fn ($query) => $query->where('organization_id', $organizationId))
            ->with(['participant:id,full_name', 'paymentList.event:id,uuid,name'])
            ->latest()
            ->paginate(20);

        return view('organization.payments', compact('payments'));
    }

    public function reports(): View
    {
        $organization = request()->user()->organization;
        $events = $organization->events()
            ->withCount(['participants', 'attendanceRecords'])
            ->withSum(['paymentLists as total_paid' => fn ($query) => $query
                ->join('participant_payments', 'payment_lists.id', '=', 'participant_payments.payment_list_id')
                ->where('participant_payments.status', 'paid')], 'participant_payments.amount')
            ->latest('starts_on')
            ->get();

        return view('organization.reports', compact('events'));
    }
}
