<?php

use App\Models\{Application, ClinicalRotation, EthicsApplication, Logbook, Student};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::middleware('permission:diklat.view')->group(function () {
        Route::get('/applications', function (Request $request) {
            $query = Application::with(['activityType','institution']);
            if ($request->user()->hasRole('applicant')) $query->where('applicant_id', $request->user()->id);
            return $query->paginate();
        });
        Route::get('/applications/{application}', function (Request $request, Application $application) {
            if ($request->user()->hasRole('applicant')) abort_unless($application->applicant_id === $request->user()->id, 403);
            return $application->load(['documents','histories','invoice']);
        });
    });

    Route::middleware('permission:kamkordik.view')->group(function () {
        Route::get('/students', function (Request $request) {
            $query = Student::with(['institution','program']);
            if ($request->user()->hasRole('student')) $query->where('user_id', $request->user()->id);
            elseif ($request->user()->hasRole('clinical-supervisor')) $query->whereHas('rotations.supervisor', fn ($q) => $q->where('user_id', $request->user()->id));
            return $query->paginate();
        });
        Route::get('/rotations', function (Request $request) {
            $query = ClinicalRotation::with(['student','department','supervisor']);
            if ($request->user()->hasRole('student')) $query->whereHas('student', fn ($q) => $q->where('user_id', $request->user()->id));
            elseif ($request->user()->hasRole('clinical-supervisor')) $query->whereHas('supervisor', fn ($q) => $q->where('user_id', $request->user()->id));
            return $query->paginate();
        });
        Route::get('/logbooks', function (Request $request) {
            $query = Logbook::with(['student','rotation']);
            if ($request->user()->hasRole('student')) $query->whereHas('student', fn ($q) => $q->where('user_id', $request->user()->id));
            elseif ($request->user()->hasRole('clinical-supervisor')) $query->whereHas('rotation.supervisor', fn ($q) => $q->where('user_id', $request->user()->id));
            return $query->paginate();
        });
    });

    Route::get('/ethics/applications', function (Request $request) {
        $query = EthicsApplication::with(['reviews','clearance']);
        if ($request->user()->hasRole('applicant')) $query->where('applicant_id', $request->user()->id);
        elseif ($request->user()->hasRole('ethics-reviewer')) $query->whereHas('reviews', fn ($q) => $q->where('reviewer_id', $request->user()->id));
        return $query->paginate();
    })->middleware('permission:ethics.view');
});
