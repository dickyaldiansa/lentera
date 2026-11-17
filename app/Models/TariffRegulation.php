<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TariffRegulation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'effective_from' => 'date',
        'is_current' => 'boolean',
    ];
}
