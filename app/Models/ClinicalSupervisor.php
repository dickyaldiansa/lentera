<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ClinicalSupervisor extends Model { protected $guarded=[]; public function department(){return $this->belongsTo(Department::class);} public function user(){return $this->belongsTo(User::class);} }
