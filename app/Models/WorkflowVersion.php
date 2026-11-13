<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkflowVersion extends Model {
    protected $guarded=[]; protected $casts=['published_at'=>'datetime'];
    public function template(){return $this->belongsTo(WorkflowTemplate::class,'workflow_template_id');}
    public function steps(){return $this->hasMany(WorkflowStep::class)->orderBy('position');}
}
