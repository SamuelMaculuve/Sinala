<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Event extends Model { use SoftDeletes; protected $guarded=[]; protected function casts(): array { return ['starts_on'=>'date','ends_on'=>'date','requires_check_in'=>'boolean','requires_check_out'=>'boolean']; } public function organization(){return $this->belongsTo(Organization::class);} public function days(){return $this->hasMany(EventDay::class);} public function participants(){return $this->belongsToMany(Participant::class,'event_participants')->withPivot('status')->withTimestamps();} public function attendanceRecords(){return $this->hasMany(AttendanceRecord::class);} public function paymentLists(){return $this->hasMany(PaymentList::class);} public function getRouteKeyName(){return 'uuid';} }
