<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Subscription extends Model { protected $guarded=[]; protected function casts(): array { return ['starts_at'=>'date','expires_at'=>'date']; } public function plan(){return $this->belongsTo(Plan::class);} public function organization(){return $this->belongsTo(Organization::class);} }
