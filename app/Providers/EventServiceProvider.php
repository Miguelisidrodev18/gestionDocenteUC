<?php

namespace App\Providers;

use App\Events\DocumentUpdated;
use App\Events\EvidenceUploaded;
use App\Listeners\UpdateCourseProgress;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        EvidenceUploaded::class => [
            UpdateCourseProgress::class,
        ],
        DocumentUpdated::class => [
            UpdateCourseProgress::class,
        ],
    ];
}

