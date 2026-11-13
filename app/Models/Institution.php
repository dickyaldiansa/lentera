<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Institution extends Model { protected $guarded=[]; protected $casts=['mou_start'=>'date','mou_end'=>'date']; }
