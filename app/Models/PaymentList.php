<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PaymentList extends Model { use SoftDeletes; protected $guarded=[]; protected function casts(): array{return ['payment_date'=>'date','default_amount'=>'decimal:2'];} public function event(){return $this->belongsTo(Event::class);} public function payments(){return $this->hasMany(ParticipantPayment::class);} public function getRouteKeyName(){return 'uuid';} }
