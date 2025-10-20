<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MeetingScheduled extends Notification
{
    use Queueable;

    public function __construct(private readonly Meeting $meeting)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'meeting',
            'meeting_id' => $this->meeting->id,
            'curso_id' => $this->meeting->curso_id,
            'title' => $this->meeting->title,
            'start_at' => $this->meeting->start_at,
            'end_at' => $this->meeting->end_at,
            'curso_nombre' => $this->meeting->curso?->nombre,
            'message' => 'Nueva reunión programada',
            'link' => '/horarios',
        ];
    }
}

