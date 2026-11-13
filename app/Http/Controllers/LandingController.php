<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;

class LandingController extends Controller
{
    public function __invoke()
    {
        return view('landing', [
            'services' => ActivityType::where('active', true)->orderBy('id')->get(),
        ]);
    }
}
