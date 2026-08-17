<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ParticipantPayment extends Model { protected $guarded=[]; protected function casts(): array{return ['paid_at'=>'datetime','amount'=>'decimal:2'];} public function paymentList(){return $this->belongsTo(PaymentList::class);} public function participant(){return $this->belongsTo(Participant::class);} public function signature(){return $this->hasOne(PaymentSignature::class);} public function getRouteKeyName(){return 'uuid';} }
