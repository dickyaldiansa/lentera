<?php

namespace App\Notifications;

use App\Models\ApplicationCoordinationTask;
use Illuminate\Notifications\Notification;

class KamkordikCoordinationNotification extends Notification
{
    public function __construct(private readonly ApplicationCoordinationTask $task) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toDatabase(object $notifiable): array
    {
        return ['title'=>'Koordinasi KAMKORDIK diperlukan','message'=>$this->task->sender->name.' meminta koordinasi untuk '.$this->task->application->number.' — '.$this->task->application->title.'.','application_id'=>$this->task->application_id,'url'=>route('kamkordik.coordinations.index')];
    }
}
