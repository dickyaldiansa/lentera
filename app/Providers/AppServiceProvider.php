<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\ApplicationCoordinationTask;
use App\Models\WorkflowTask;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
                URL::forceScheme('https');
        }

        View::composer('layouts.app',function($view){
            $count=0;$applicantCount=0;
            if(auth()->check() && auth()->user()->hasRole('super-admin','diklat-admin','diklat-head')){
                $count=Application::whereIn('status',['draft','submitted','waiting_approval'])->count();
            }
            if(auth()->check() && auth()->user()->hasRole('applicant')){
                $applicantCount=Application::where('applicant_id',auth()->id())->whereIn('status',['revision_required','waiting_payment'])->count();
            }
            $approvalTaskCount=auth()->check() && auth()->user()->canDo('workflow.approve')
                ? WorkflowTask::forUser(auth()->user())->where('status','pending')->count() : 0;
            $coordinationTaskCount=auth()->check() && auth()->user()->canDo('kamkordik.view')
                ? ApplicationCoordinationTask::where('recipient_id',auth()->id())->where('status','pending')->count() : 0;
            $notificationCount=auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
            $view->with(['newApplicationCount'=>$count,'applicantNotificationCount'=>$applicantCount,'approvalTaskCount'=>$approvalTaskCount,'coordinationTaskCount'=>$coordinationTaskCount,'notificationCount'=>$notificationCount]);
        });
    }
}