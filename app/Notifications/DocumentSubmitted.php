<?php

namespace App\Notifications;

use App\Models\CourseDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentSubmitted extends Notification
{
    use Queueable;

    public function __construct(private readonly CourseDocument $document)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $curso = $this->document->curso;
        $docente = $this->document->docente;
        $docenteNombre = $docente ? trim($docente->nombre . ' ' . $docente->apellido) : 'Desconocido';

        return (new MailMessage)
            ->subject('Nuevo documento cargado')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Se ha cargado un nuevo documento para el curso: ' . ($curso?->nombre ?? 'Curso'))
            ->line('Docente que subió el archivo: ' . $docenteNombre)
            ->action('Revisar documento', url('/cursos'))
            ->line('Puedes revisar el checklist y dar el conforme preliminar cuando corresponda.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'curso_id' => $this->document->curso_id,
            'document_id' => $this->document->id,
            'status' => $this->document->status,
        ];
    }
}
