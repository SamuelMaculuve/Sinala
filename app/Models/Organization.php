<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Organization extends Model { use SoftDeletes; protected $guarded=[]; protected function casts(): array { return ['report_settings'=>'array']; } public function users(){return $this->hasMany(User::class);} public function events(){return $this->hasMany(Event::class);} public function participants(){return $this->hasMany(Participant::class);} public function subscription(){return $this->hasOne(Subscription::class)->latestOfMany();} }
