<?php

namespace App\Notifications;

use App\Models\Update;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpdatePublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Update $update,
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (config('queue.default') !== 'sync') {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nuevo aviso en Portafolio Docente')
            ->greeting('Hola '.$notifiable->name)
            ->line($this->update->titulo)
            ->line('Hay una nueva actualización en el módulo de Portafolio Docente.')
            ->action('Ver actualización', url('/actualizaciones'))
            ->line('Por favor revisa y confirma la lectura si aplica.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'update_published',
            'message' => $this->update->titulo,
            'link' => '/actualizaciones',
            'update_id' => $this->update->id,
        ];
    }
}
