<?php

namespace App\Notifications;

use App\Models\AdvisorAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PanelInvitationNotification extends Notification implements ShouldQueue
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
            ->subject('Invitación a panel de asesoría/jurado')
            ->greeting('Hola '.$notifiable->name)
            ->line('Has sido invitado(a) a participar en un panel.')
            ->line('Curso: '.$curso->codigo.' - '.$curso->nombre)
            ->line('Rol asignado: '.ucfirst($this->assignment->role))
            ->line('Estado actual: '.$this->assignment->status)
            ->line('Puedes aceptar o rechazar la invitación desde el módulo "Asesores y Jurados" en la plataforma.');
    }

    public function toArray(object $notifiable): array
    {
        $panel = $this->assignment->panel;
        $curso = $panel->curso;

        return [
            'type' => 'panel_invitation',
            'panel_id' => $panel->id,
            'curso_id' => $curso->id,
            'curso_codigo' => $curso->codigo,
            'curso_nombre' => $curso->nombre,
            'role' => $this->assignment->role,
            'status' => $this->assignment->status,
        ];
    }
}

