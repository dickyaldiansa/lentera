<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkflowAction extends Model {
    protected $guarded=[]; protected $casts=['acted_at'=>'datetime','metadata'=>'array'];
    public function task(){return $this->belongsTo(WorkflowTask::class,'workflow_task_id');}
    public function user(){return $this->belongsTo(User::class);}
}
