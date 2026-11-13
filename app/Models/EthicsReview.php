<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EthicsReview extends Model { protected $guarded=[]; protected $casts=['scores'=>'array','section_notes'=>'array','assigned_at'=>'date','deadline'=>'date','completed_at'=>'datetime']; public function application(){return $this->belongsTo(EthicsApplication::class,'ethics_application_id');} public function reviewer(){return $this->belongsTo(User::class,'reviewer_id');} }
