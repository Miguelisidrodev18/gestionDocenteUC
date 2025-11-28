<?php

namespace App\Notifications;

use App\Models\AdvisorAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PanelAssignmentStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected AdvisorAssignment $assignment,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $panel = $this->assignment->panel;
        $curso = $panel->curso;

        return (new MailMessage)
            ->subject('Respuesta a invitación de panel')
            ->greeting('Hola '.$notifiable->name)
            ->line('El docente '.$this->assignment->docente->nombre.' '.$this->assignment->docente->apellido)
            ->line('ha '.($this->assignment->status === 'aceptado' ? 'aceptado' : 'rechazado').' la invitación.')
            ->line('Curso: '.$curso->codigo.' - '.$curso->nombre)
            ->line('Rol: '.ucfirst($this->assignment->role))
            ->line('Estado: '.$this->assignment->status);
    }

    public function toArray(object $notifiable): array
    {
        $panel = $this->assignment->panel;
        $curso = $panel->curso;

        return [
            'type' => 'panel_assignment_status',
            'panel_id' => $panel->id,
            'curso_id' => $curso->id,
            'curso_codigo' => $curso->codigo,
            'curso_nombre' => $curso->nombre,
            'docente' => $this->assignment->docente->nombre.' '.$this->assignment->docente->apellido,
            'role' => $this->assignment->role,
            'status' => $this->assignment->status,
        ];
    }
}

