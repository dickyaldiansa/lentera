<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkflowInstance extends Model {
    protected $guarded=[]; protected $casts=['started_at'=>'datetime','completed_at'=>'datetime'];
    public function version(){return $this->belongsTo(WorkflowVersion::class,'workflow_version_id');}
    public function workflowable(){return $this->morphTo();}
    public function currentStep(){return $this->belongsTo(WorkflowStep::class,'current_step_id');}
    public function tasks(){return $this->hasMany(WorkflowTask::class);}
}
