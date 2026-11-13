<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationParticipant extends Model
{
    protected $guarded=[];

    public function application(){return $this->belongsTo(Application::class);}
    public function certificate(){return $this->hasOne(Certificate::class);}
}
