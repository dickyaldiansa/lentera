<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GuideController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $sections = collect([
            'applicant' => $user->hasRole('applicant') || $user->hasRole('super-admin'),
            'diklat' => $user->canDo('diklat.verify') || $user->canDo('diklat.approve') || $user->hasRole('super-admin'),
            'kamkordik' => $user->canDo('kamkordik.view') || $user->hasRole('super-admin'),
            'ethics' => ($user->canDo('ethics.review') || $user->canDo('ethics.approve')) && ! $user->hasRole('applicant') || $user->hasRole('super-admin'),
        ])->filter()->keys()->all();

        return view('guide', compact('sections'));
    }
}
