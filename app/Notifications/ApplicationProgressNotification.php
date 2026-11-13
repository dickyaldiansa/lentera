<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Notification;

class ApplicationProgressNotification extends Notification
{
    public function __construct(private Application $application, private string $message, private ?string $url = null) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toDatabase(object $notifiable): array
    {
        return ['title' => 'Pembaruan Pengajuan', 'message' => $this->message,
            'application_id' => $this->application->id,
            'url' => $this->url ?? route('applications.show', $this->application)];
    }
}
