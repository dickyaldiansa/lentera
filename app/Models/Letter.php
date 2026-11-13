<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Letter extends Model { protected $guarded=[]; protected $casts=['issued_at'=>'date']; public function letterable(){return $this->morphTo();} }
