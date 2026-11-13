<?php
namespace App\Services;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class AuditService {
 public static function record(string $module,string $action,?Model $model=null,array $old=[],array $new=[],?string $description=null): void {
  DB::table('audit_logs')->insert(['user_id'=>auth()->id(),'ip_address'=>request()?->ip(),'module'=>$module,'action'=>$action,'auditable_type'=>$model?->getMorphClass(),'auditable_id'=>$model?->getKey(),'old_values'=>$old?json_encode($old):null,'new_values'=>$new?json_encode($new):null,'description'=>$description,'created_at'=>now(),'updated_at'=>now()]);
 }
}
