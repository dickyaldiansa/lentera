<?php

namespace App\Notifications;

use App\Models\ApplicationCoordinationTask;
use Illuminate\Notifications\Notification;

class KamkordikScheduleNotification extends Notification
{
    public function __construct(private readonly ApplicationCoordinationTask $task) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        $labels = [
            'needs_information' => 'KAMKORDIK memerlukan data tambahan',
            'scheduled' => 'Jadwal persamaan persepsi telah ditetapkan',
            'handover_completed' => 'Serah terima telah dikonfirmasi',
            'not_ready' => 'Koordinasi belum siap dilanjutkan',
        ];
        $message = $labels[$this->task->status] ?? 'Ada pembaruan koordinasi KAMKORDIK';
        if ($this->task->scheduled_at) $message .= ': '.$this->task->scheduled_at->translatedFormat('l, d F Y · H:i').' WIB';

        return [
            'title' => 'Pembaruan Koordinasi KAMKORDIK',
            'message' => $message,
            'application_id' => $this->task->application_id,
            'url' => route('applications.show', $this->task->application_id),
        ];
    }
}
