<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Invoice extends Model { protected $guarded=[]; protected $casts=['items'=>'array','due_date'=>'date']; public function application(){return $this->belongsTo(Application::class);} public function payments(){return $this->hasMany(Payment::class);} }
