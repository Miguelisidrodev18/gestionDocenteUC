<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewResponsibleAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Assignment $assignment
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $curso = $this->assignment->curso;

        return (new MailMessage())
            ->subject('Asignación como responsable de curso')
            ->greeting('Hola '.$notifiable->name)
            ->line('Has sido asignado como responsable del curso:')
            ->line(($curso->codigo ?? '').' - '.($curso->nombre ?? ''))
            ->line('Periodo: '.$curso->periodo)
            ->when($this->assignment->modalidad_docente, function ($msg) {
                $msg->line('Modalidad docente: '.$this->assignment->modalidad_docente);
            })
            ->action('Ver curso', url('/cursos/'.$curso->id))
            ->line('Por favor revisa el curso y registra el avance correspondiente.');
    }

    public function toArray(object $notifiable): array
    {
        $curso = $this->assignment->curso;

        return [
            'curso_id' => $curso->id,
            'codigo' => $curso->codigo,
            'nombre' => $curso->nombre,
            'periodo' => $curso->periodo,
            'modalidad_docente' => $this->assignment->modalidad_docente,
        ];
    }
}

