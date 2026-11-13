<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ApplicationStatusHistory extends Model { protected $guarded=[]; public function actor(){return $this->belongsTo(User::class,'changed_by');} }
