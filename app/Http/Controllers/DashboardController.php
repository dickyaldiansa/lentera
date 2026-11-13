<?php

namespace App\Http\Controllers;

use App\Models\{Activity, Application, EthicalClearance, EthicsApplication, Payment, Student};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $access = [
            'applicant' => $user->hasRole('applicant'),
            'diklat' => $user->canDo('diklat.view'),
            'kamkordik' => $user->canDo('kamkordik.view'),
            'ethics' => $user->canDo('ethics.view') && !$user->hasRole('applicant'),
        ];

        $applications = Application::query();
        $ethics = EthicsApplication::query();
        $students = Student::query();
        $activities = Activity::query();
        $payments = Payment::query();

        if ($access['applicant']) {
            $applications->where('applicant_id', $user->id);
            $ethics->where('applicant_id', $user->id);
            $activities->whereHas('application', fn ($query) => $query->where('applicant_id', $user->id));
            $payments->whereHas('invoice.application', fn ($query) => $query->where('applicant_id', $user->id));
        }

        if ($user->hasRole('student')) {
            $students->where('user_id', $user->id);
        } elseif ($user->hasRole('clinical-supervisor')) {
            $students->whereHas('rotations.supervisor', fn ($query) => $query->where('user_id', $user->id));
        }

        $stats = [
            'applications' => $access['diklat'] ? (clone $applications)->count() : 0,
            'waiting' => $access['diklat'] ? (clone $applications)->whereIn('status', ['submitted','administrative_verification','waiting_approval'])->count() : 0,
            'students' => $access['kamkordik'] ? (clone $students)->where('status', 'active')->count() : 0,
            'ethics' => $access['ethics'] ? (clone $ethics)->whereNotIn('status', ['closed','rejected'])->count() : 0,
            'activities' => $access['diklat'] ? (clone $activities)->where('status', 'running')->count() : 0,
            'payments_total' => $access['diklat'] ? (clone $payments)->where('status', 'verified')->sum('amount') : 0,
            'clearances' => $access['ethics'] ? EthicalClearance::whereHas('application', function ($query) use ($user, $access) {
                if ($access['applicant']) $query->where('applicant_id', $user->id);
            })->where('status', 'active')->count() : 0,
        ];

        $recent = $access['diklat'] ? (clone $applications)->with(['applicant','activityType'])->latest()->limit(6)->get() : collect();
        $monthly = $access['diklat'] ? (clone $applications)->selectRaw("substr(created_at,1,7) month, count(*) total")->groupBy('month')->orderBy('month')->limit(8)->get() : collect();
        $recentPayments = $access['diklat'] ? (clone $payments)->with(['invoice.application.applicant'])->latest('paid_at')->limit(8)->get() : collect();

        $audiences = ['all'];
        if ($access['diklat']) $audiences[] = 'diklat';
        if ($access['kamkordik']) $audiences[] = 'kamkordik';
        if ($access['ethics']) $audiences[] = 'ethics';
        $announcements = DB::table('announcements')->whereIn('audience', $audiences)->where('published_at','<=',now())->where(fn($q)=>$q->whereNull('expires_at')->orWhere('expires_at','>',now()))->latest('published_at')->limit(3)->get();

        return view('dashboard', compact('stats','recent','monthly','recentPayments','announcements','access'));
    }
}
