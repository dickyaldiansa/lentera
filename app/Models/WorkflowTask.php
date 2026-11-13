<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class WorkflowTask extends Model {
    protected $guarded=[]; protected $casts=['due_at'=>'datetime','acted_at'=>'datetime'];
    public function instance(){return $this->belongsTo(WorkflowInstance::class,'workflow_instance_id');}
    public function step(){return $this->belongsTo(WorkflowStep::class,'workflow_step_id');}
    public function actor(){return $this->belongsTo(User::class,'acted_by');}
    public function actions(){return $this->hasMany(WorkflowAction::class);}
    public function scopeForUser(Builder $query,User $user): Builder {
        $roles=$user->roles->pluck('slug');
        $delegatedFrom=DB::table('workflow_delegations')->where('to_user_id',$user->id)->where('active',true)
            ->where('starts_at','<=',now())->where('ends_at','>=',now())->pluck('from_user_id');
        return $query->where(function($q) use($user,$roles,$delegatedFrom){
            $q->where('assignee_user_id',$user->id)->orWhereIn('assignee_role_slug',$roles)
              ->orWhereIn('assignee_user_id',$delegatedFrom);
        });
    }
}
