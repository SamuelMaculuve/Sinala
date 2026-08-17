<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AttendanceSignature extends Model { protected $guarded=[]; public function record(){return $this->belongsTo(AttendanceRecord::class,'attendance_record_id');} }
