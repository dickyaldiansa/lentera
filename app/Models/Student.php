<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Student extends Model { protected $guarded=[]; protected $casts=['period_start'=>'date','period_end'=>'date']; public function institution(){return $this->belongsTo(Institution::class);} public function department(){return $this->belongsTo(Department::class);} public function program(){return $this->belongsTo(EducationProgram::class,'education_program_id');} public function rotations(){return $this->hasMany(ClinicalRotation::class);} }
