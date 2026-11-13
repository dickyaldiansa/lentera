<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class NewApplicationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Application $application) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage|array
    {
        return [
            'title' => 'Pengajuan permohonan baru',
            'message' => $this->application->applicant->name.' membuat pengajuan “'.$this->application->title.'”.',
            'application_id' => $this->application->id,
            'url' => route('applications.show', $this->application),
        ];
    }
}
