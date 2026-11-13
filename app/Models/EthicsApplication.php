<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EthicsApplication extends Model { protected $guarded=[]; protected $casts=['members'=>'array','start_date'=>'date','end_date'=>'date','submitted_at'=>'datetime']; public function applicant(){return $this->belongsTo(User::class,'applicant_id');} public function institution(){return $this->belongsTo(Institution::class);} public function reviews(){return $this->hasMany(EthicsReview::class);} public function clearance(){return $this->hasOne(EthicalClearance::class);} public function workflowInstance(){return $this->morphOne(WorkflowInstance::class,'workflowable');} public function application(){return $this->belongsTo(Application::class);} }
