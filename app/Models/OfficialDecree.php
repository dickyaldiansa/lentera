<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialDecree extends Model
{
    protected $guarded = [];

    protected $casts = [
        'issued_at' => 'date',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
