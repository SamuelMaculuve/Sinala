<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\SubscriptionService;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventController extends Controller {
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $events=request()->user()->organization->events()->withCount('participants')->latest('starts_on')->paginate(12); return view('events.index',compact('events'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_unless(app(SubscriptionService::class)->canCreateEvent(request()->user()->organization),403,'Atingiu o limite de eventos do seu plano.'); return view('events.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $org=$request->user()->organization; abort_unless(app(SubscriptionService::class)->canCreateEvent($org),403,'Atingiu o limite de eventos do seu plano.'); $data=$request->validate(['name'=>'required|max:180','type'=>'required|in:training,workshop,seminar,conference,meeting,community,capacity,other','location'=>'required|max:180','province'=>'nullable|max:100','district'=>'nullable|max:100','starts_on'=>'required|date','ends_on'=>'required|date|after_or_equal:starts_on','starts_at'=>'nullable','ends_at'=>'nullable','facilitator'=>'nullable|max:150','responsible_name'=>'nullable|max:150','contact'=>'nullable|max:50','expected_participants'=>'nullable|integer|min:0','description'=>'nullable|max:3000','notes'=>'nullable|max:3000','requires_check_out'=>'nullable|boolean']); $event=DB::transaction(function()use($org,$data){$event=$org->events()->create($data+['uuid'=>Str::uuid(),'public_code'=>Str::upper(Str::random(10)),'status'=>'scheduled']); foreach(CarbonPeriod::create($data['starts_on'],$data['ends_on']) as $date)$event->days()->create(['date'=>$date]); return $event;}); return redirect()->route('events.show',$event)->with('success','Evento criado com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        $this->authorize('view',$event);
        $event->load(['days','paymentLists.payments'])->loadCount('participants');
        $participants=$event->participants()->orderBy('full_name')->paginate(20,['participants.*'],'participants_page')->withQueryString();
        $participantAttendances = $event->attendanceRecords()
            ->whereIn('participant_id', $participants->getCollection()->pluck('id'))
            ->whereHas('signature')
            ->latest('recorded_at')
            ->get()
            ->groupBy('participant_id');
        $syncCandidatesCount = $event->participants()
            ->wherePivot('status', 'pending')
            ->whereIn('participants.id', $event->attendanceRecords()->select('participant_id')->distinct())
            ->count();
        return view('events.show',compact('event','participants','participantAttendances','syncCandidatesCount'));
    }

    public function syncAttendanceStatuses(Event $event)
    {
        $this->authorize('update', $event);

        $participantIds = $event->attendanceRecords()
            ->select('participant_id')
            ->distinct()
            ->pluck('participant_id');

        $updated = 0;
        DB::transaction(function () use ($event, $participantIds, &$updated) {
            foreach ($participantIds as $participantId) {
                $updated += $event->participants()
                    ->wherePivot('status', '!=', 'present')
                    ->updateExistingPivot($participantId, ['status' => 'present']);
            }
        });

        return back()->with('success', $updated > 0
            ? "Estados sincronizados com sucesso para {$updated} participante(s)."
            : 'Nenhum estado precisava de correcção.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event)
    {
        $this->authorize('editEventDetails',$event); return view('events.edit',compact('event'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        $this->authorize('editEventDetails',$event); $data=$request->validate(['name'=>'required|max:180','type'=>'required|in:training,workshop,seminar,conference,meeting,community,capacity,other','status'=>'required|in:draft,scheduled,ongoing,completed,cancelled','location'=>'required|max:180','province'=>'nullable|max:100','district'=>'nullable|max:100','starts_on'=>'required|date','ends_on'=>'required|date|after_or_equal:starts_on','starts_at'=>'nullable','ends_at'=>'nullable','facilitator'=>'nullable|max:150','responsible_name'=>'nullable|max:150','contact'=>'nullable|max:50','expected_participants'=>'nullable|integer|min:0','description'=>'nullable|max:3000','requires_check_out'=>'nullable|boolean']);
        DB::transaction(function()use($event,$data){$event->update($data); $existing=$event->days()->pluck('date')->map(fn($d)=>$d->format('Y-m-d'))->all(); foreach(CarbonPeriod::create($data['starts_on'],$data['ends_on']) as $date)if(!in_array($date->format('Y-m-d'),$existing,true))$event->days()->create(['date'=>$date]);});
        return redirect()->route('events.show',$event)->with('success','Evento actualizado.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        $this->authorize('delete',$event); $event->delete(); return redirect()->route('events.index')->with('success','Evento removido.');
    }

    /**
     * Close the event, permanently blocking further changes.
     */
    public function close(Request $request, Event $event)
    {
        $this->authorize('close',$event); $event->update(['closed_at'=>now(),'closed_by'=>$request->user()->id]); return redirect()->route('events.show',$event)->with('success','Evento fechado. Já não é possível alterar dados.');
    }
}
