<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Logbook extends Model { protected $guarded=[]; protected $casts=['activity_date'=>'date','submitted_at'=>'datetime','verified_at'=>'datetime']; public function student(){return $this->belongsTo(Student::class);} public function rotation(){return $this->belongsTo(ClinicalRotation::class,'clinical_rotation_id');} }
