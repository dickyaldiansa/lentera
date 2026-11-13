<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Application extends Model {
 use SoftDeletes; protected $guarded=[]; protected $casts=['start_date'=>'date','end_date'=>'date','submitted_at'=>'datetime'];
 public function applicant(){return $this->belongsTo(User::class,'applicant_id');}
 public function activityType(){return $this->belongsTo(ActivityType::class);}
 public function institution(){return $this->belongsTo(Institution::class);}
 public function department(){return $this->belongsTo(Department::class);}
 public function histories(){return $this->hasMany(ApplicationStatusHistory::class)->latest();}
 public function documents(){return $this->hasMany(ApplicationDocument::class);}
 public function participants(){return $this->hasMany(ApplicationParticipant::class);}
 public function certificates(){return $this->hasMany(Certificate::class);}
 public function invoice(){return $this->hasOne(Invoice::class);}
 public function workflowInstance(){return $this->morphOne(WorkflowInstance::class,'workflowable');}
 public function coordinationTasks(){return $this->hasMany(ApplicationCoordinationTask::class);}
 public function usesPaymentGate(): bool {return (bool)$this->workflowInstance?->version->steps->contains(fn($step)=>(bool)($step->conditions['payment_gate']??false));}
 public function ethicsApplication(){return $this->hasOne(EthicsApplication::class);}
 public function letters(){return $this->morphMany(Letter::class,'letterable');}
 public function certificateEligible(): bool {
  if($this->status!=='completed')return false;
  $final=$this->workflowInstance?->version->steps->last();
  return !($final?->conditions['managed']??false)||($final->conditions['outcome']??'information')==='certificate';
 }
}
