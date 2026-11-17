<?php

namespace App\Http\Controllers;

use App\Models\Tariff;

class TariffController extends Controller
{
    public function index()
    {
        $tariffs=Tariff::visible()->orderBy('sort_order')->orderBy('id')->get()->groupBy('category');
        $regulations=\App\Models\TariffRegulation::orderByDesc('is_current')->orderByDesc('year')->orderByDesc('id')->get();
        return view('tariffs.index',compact('tariffs','regulations'));
    }
}
