<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Activity extends Model { protected $guarded=[]; protected $casts=['start_at'=>'datetime','end_at'=>'datetime']; public function application(){return $this->belongsTo(Application::class);} }
