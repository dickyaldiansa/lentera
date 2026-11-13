<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EthicalClearance extends Model { protected $guarded=[]; protected $casts=['issued_at'=>'date','valid_until'=>'date']; public function application(){return $this->belongsTo(EthicsApplication::class,'ethics_application_id');} }
