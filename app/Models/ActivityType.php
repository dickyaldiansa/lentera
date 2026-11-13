<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ActivityType extends Model { protected $guarded=[]; protected $casts=['is_paid'=>'boolean','requires_ethics'=>'boolean','requires_kamkordik'=>'boolean']; }
