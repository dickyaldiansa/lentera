<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ClinicalRotation extends Model { protected $guarded=[]; protected $casts=['start_date'=>'date','end_date'=>'date']; public function student(){return $this->belongsTo(Student::class);} public function department(){return $this->belongsTo(Department::class);} public function supervisor(){return $this->belongsTo(ClinicalSupervisor::class,'clinical_supervisor_id');} }
