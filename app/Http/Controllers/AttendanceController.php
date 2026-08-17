<?php

namespace App\Http\Controllers;

use App\Models\{AttendanceRecord,AttendanceSignature,Event};
use App\Services\SignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttendanceController extends Controller {
    public function kiosk(Request $r,Event $event){$this->authorize('view',$event);$event->load(['days','participants']);return view('attendance.kiosk',compact('event'));}
    public function store(Request $r,Event $event,SignatureService $signatures){$this->authorize('update',$event);$data=$r->validate(['participant_id'=>'required|integer','event_day_id'=>'required|integer','type'=>'required|in:check_in,check_out','signature'=>'required|string']);$participant=$event->participants()->whereKey($data['participant_id'])->firstOrFail();$day=$event->days()->whereKey($data['event_day_id'])->firstOrFail();abort_if(AttendanceRecord::where(['event_day_id'=>$day->id,'participant_id'=>$participant->id,'type'=>$data['type']])->exists(),422,'Esta presença já foi registada.');$file=$signatures->store($data['signature'],$event->organization_id,'attendance');DB::transaction(function()use($event,$participant,$day,$data,$r,$file){$record=AttendanceRecord::create(['uuid'=>Str::uuid(),'event_id'=>$event->id,'event_day_id'=>$day->id,'participant_id'=>$participant->id,'type'=>$data['type'],'status'=>'present','recorded_at'=>now(),'recorded_by'=>$r->user()->id,'ip_address'=>$r->ip()]);AttendanceSignature::create($file+['attendance_record_id'=>$record->id]);});return back()->with('success','Presença registada com sucesso.');}
}
