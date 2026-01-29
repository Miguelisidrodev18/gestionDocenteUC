<?php

namespace App\Jobs;

use App\Models\Update;
use App\Models\User;
use App\Notifications\UpdatePublished;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class NotifyUpdateAudience implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $updateId,
    ) {
    }

    public function handle(): void
    {
        $update = Update::find($this->updateId);

        if (! $update) {
            return;
        }

        $query = User::query();

        switch ($update->audience) {
            case Update::AUDIENCE_DOCENTES:
                $query->whereHas('docente');
                break;
            case Update::AUDIENCE_RESPONSABLES:
                $query->where('role', 'responsable');
                break;
            case Update::AUDIENCE_ADMIN:
                $query->where('role', 'admin');
                break;
            case Update::AUDIENCE_TODOS:
            default:
                // sin filtros
                break;
        }

        $users = $query->get();

        Notification::send($users, new UpdatePublished($update));
    }
}
