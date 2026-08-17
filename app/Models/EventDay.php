<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EventDay extends Model { protected $guarded=[]; protected function casts(): array{return ['date'=>'date'];} public function event(){return $this->belongsTo(Event::class);} }
