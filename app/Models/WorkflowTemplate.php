<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkflowTemplate extends Model {
    protected $guarded=[]; protected $casts=['active'=>'boolean'];
    public function activityType(){return $this->belongsTo(ActivityType::class);}
    public function versions(){return $this->hasMany(WorkflowVersion::class);}
    public function latestVersion(){return $this->hasOne(WorkflowVersion::class)->latestOfMany('version_number');}
    public function publishedVersion(){return $this->hasOne(WorkflowVersion::class)->ofMany(['version_number'=>'max'],fn($q)=>$q->where('status','published'));}
}
