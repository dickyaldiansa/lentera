<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $guarded=[];
    protected $casts=['issued_at'=>'date'];

    public function application(){return $this->belongsTo(Application::class);}
    public function participant(){return $this->belongsTo(ApplicationParticipant::class);}
    public function issuer(){return $this->belongsTo(User::class,'issued_by');}
}
