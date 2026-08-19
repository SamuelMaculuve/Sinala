<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Event extends Model { use SoftDeletes; protected $guarded=[]; protected function casts(): array { return ['starts_on'=>'date','ends_on'=>'date','closed_at'=>'datetime','requires_check_in'=>'boolean','requires_check_out'=>'boolean']; } public function organization(){return $this->belongsTo(Organization::class);} public function days(){return $this->hasMany(EventDay::class);} public function participants(){return $this->belongsToMany(Participant::class,'event_participants')->withPivot('status')->withTimestamps();} public function attendanceRecords(){return $this->hasMany(AttendanceRecord::class);} public function paymentLists(){return $this->hasMany(PaymentList::class);} public function closedBy(){return $this->belongsTo(User::class,'closed_by');} public function getRouteKeyName(){return 'uuid';}
    public function isClosed(): bool { return !is_null($this->closed_at); }
    public function endsAtMoment(): \Carbon\Carbon { return $this->ends_at ? \Carbon\Carbon::parse($this->ends_on->format('Y-m-d').' '.$this->ends_at) : $this->ends_on->copy()->endOfDay(); }
    public function hasEnded(): bool { return now()->greaterThan($this->endsAtMoment()); }
    public function canBeEdited(): bool { return !$this->isClosed() && !$this->hasEnded(); }
    public function canBeClosed(): bool { return !$this->isClosed() && $this->hasEnded(); }
}
