<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationCoordinationTask extends Model
{
    protected $guarded = [];
    protected $casts = ['responded_at' => 'datetime', 'scheduled_at' => 'datetime', 'handover_completed_at' => 'datetime', 'notify_applicant' => 'boolean'];
    public function application(){ return $this->belongsTo(Application::class); }
    public function sender(){ return $this->belongsTo(User::class,'sender_id'); }
    public function recipient(){ return $this->belongsTo(User::class,'recipient_id'); }
}
