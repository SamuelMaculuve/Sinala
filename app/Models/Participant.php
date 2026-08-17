<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Participant extends Model { use SoftDeletes; protected $guarded=[]; protected function casts(): array{return ['birth_date'=>'date'];} public function events(){return $this->belongsToMany(Event::class,'event_participants')->withPivot('status')->withTimestamps();} public function organization(){return $this->belongsTo(Organization::class);} public function getRouteKeyName(){return 'uuid';} }
