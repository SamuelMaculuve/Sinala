<?php

namespace App\Http\Controllers;

use App\Models\{AttendanceRecord,AttendanceSignature,Event};
use App\Services\SignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttendanceController extends Controller {
    public function kiosk(Request $r,Event $event){$this->authorize('view',$event);$event->load(['days','participants'=>fn($query)=>$query->orderBy('full_name'),'attendanceRecords.signature']);return view('attendance.kiosk',compact('event'));}
    public function store(Request $r,Event $event,SignatureService $signatures){$this->authorize('update',$event);$data=$r->validate(['participant_id'=>'required|integer','event_day_id'=>'required|integer','type'=>'required|in:check_in,check_out','signature'=>'required|string','replace_existing'=>'nullable|boolean']);$participant=$event->participants()->whereKey($data['participant_id'])->firstOrFail();$day=$event->days()->whereKey($data['event_day_id'])->firstOrFail();$record=AttendanceRecord::where(['event_day_id'=>$day->id,'participant_id'=>$participant->id,'type'=>$data['type']])->first();if($record && !($data['replace_existing'] ?? false)) return back()->withErrors(['attendance'=>'Esta presença já foi registada.'])->with('attendance_can_update',true)->withInput();$file=$signatures->store($data['signature'],$event->organization_id,'attendance');DB::transaction(function()use($event,$participant,$day,$data,$r,$file,$record){$record=$record?tap($record)->update(['status'=>'present','recorded_at'=>now(),'recorded_by'=>$r->user()->id,'ip_address'=>$r->ip()]):AttendanceRecord::create(['uuid'=>Str::uuid(),'event_id'=>$event->id,'event_day_id'=>$day->id,'participant_id'=>$participant->id,'type'=>$data['type'],'status'=>'present','recorded_at'=>now(),'recorded_by'=>$r->user()->id,'ip_address'=>$r->ip()]);AttendanceSignature::updateOrCreate(['attendance_record_id'=>$record->id],$file+['attendance_record_id'=>$record->id]);$event->participants()->updateExistingPivot($participant->id,['status'=>'present']);});return back()->with('success',$record?'Presença actualizada com sucesso.':'Presença registada com sucesso.');}
    public function signature(AttendanceRecord $attendanceRecord){$attendanceRecord->load(['event','signature']);$this->authorize('view',$attendanceRecord->event);abort_unless($attendanceRecord->signature?->path && Storage::disk('local')->exists($attendanceRecord->signature->path),404);return Storage::disk('local')->response($attendanceRecord->signature->path,null,['Cache-Control'=>'private, max-age=3600']);}
}
