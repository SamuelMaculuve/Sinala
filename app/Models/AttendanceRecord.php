<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AttendanceRecord extends Model { protected $guarded=[]; protected function casts(): array{return ['recorded_at'=>'datetime'];} public function signature(){return $this->hasOne(AttendanceSignature::class);} public function participant(){return $this->belongsTo(Participant::class);} public function event(){return $this->belongsTo(Event::class);} }
