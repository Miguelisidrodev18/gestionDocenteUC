<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingScheduled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Meeting $meeting)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $curso = $this->meeting->curso;

        return (new MailMessage())
            ->subject('Nueva reunión programada')
            ->greeting('Hola '.$notifiable->name)
            ->line('Se ha programado una nueva reunión del curso:')
            ->line(($curso?->codigo ?? '').' - '.($curso?->nombre ?? ''))
            ->line('Título: '.$this->meeting->title)
            ->line('Inicio: '.$this->meeting->start_at)
            ->line('Fin: '.$this->meeting->end_at)
            ->when($this->meeting->location, function ($msg) {
                $msg->line('Lugar / link: '.$this->meeting->location);
            })
            ->action('Ver calendario', url('/horarios'));
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

