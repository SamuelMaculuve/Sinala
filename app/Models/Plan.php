<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Plan extends Model { protected $guarded=[]; protected function casts(): array { return ['features'=>'array','monthly_event_limit'=>'boolean','active'=>'boolean']; } public function subscriptions(){return $this->hasMany(Subscription::class);} }
