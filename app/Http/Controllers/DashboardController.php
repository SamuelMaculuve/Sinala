<?php

namespace App\Http\Controllers;

use App\Models\{AttendanceRecord,ParticipantPayment};
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class DashboardController extends Controller {
    public function __invoke(Request $request, SubscriptionService $subscriptions)
    {
        $org=$request->user()->organization;
        $events=$org->events();
        $eventIds=(clone $events)->pluck('id');
        $participants=$org->participants()->count();
        $present=AttendanceRecord::whereIn('event_id',$eventIds)->distinct('participant_id')->count('participant_id');
        $stats=[
            'events'=>(clone $events)->count(),
            'completed'=>(clone $events)->where('status','completed')->count(),
            'ongoing'=>(clone $events)->where('status','ongoing')->count(),
            'upcoming'=>(clone $events)->where('starts_on','>',today())->count(),
            'participants'=>$participants,
            'present'=>$present,
            'attendance_rate'=>$participants ? round(($present/$participants)*100,1) : 0,
            'distributed'=>ParticipantPayment::whereHas('paymentList.event',fn($q)=>$q->where('organization_id',$org->id))->where('status','paid')->sum('amount'),
            'signatures'=>AttendanceRecord::whereIn('event_id',$eventIds)->whereHas('signature')->count(),
        ];
        $recentEvents=$org->events()->withCount(['participants','attendanceRecords'])->latest('starts_on')->limit(8)->get();

        return view('dashboard',compact('org','stats','recentEvents'))->with('usage',$subscriptions->usage($org));
    }
}
