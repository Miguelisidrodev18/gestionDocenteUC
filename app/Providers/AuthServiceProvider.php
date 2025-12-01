<?php

namespace App\Providers;

use App\Models\Acta;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Evidencia;
use App\Models\InformeFinal;
use App\Models\FinalResult;
use App\Models\ImprovementOpportunity;
use App\Models\Meeting;
use App\Models\RegistroNota;
use App\Models\Area;
use App\Models\Modalidad;
use App\Models\Sede;
use App\Models\Campus;
use App\Models\PeriodoAcademico;
use App\Models\TipoEvidencia;
use App\Models\Bloque;
use App\Models\RequisitoModalidad;
use App\Models\Update;
use App\Policies\CoursePolicy;
use App\Models\AdvisorProfile;
use App\Models\Panel;
use App\Policies\DocentePolicy;
use App\Policies\EvidencePolicy;
use App\Policies\FinalReportPolicy;
use App\Policies\FinalResultPolicy;
use App\Policies\ImprovementOpportunityPolicy;
use App\Policies\GradesheetPolicy;
use App\Policies\MeetingPolicy;
use App\Policies\MinutePolicy;
use App\Policies\CatalogPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Curso::class => CoursePolicy::class,
        Evidencia::class => EvidencePolicy::class,
        Acta::class => MinutePolicy::class,
        RegistroNota::class => GradesheetPolicy::class,
        InformeFinal::class => FinalReportPolicy::class,
        Meeting::class => MeetingPolicy::class,
        Docente::class => DocentePolicy::class,
        FinalResult::class => FinalResultPolicy::class,
        ImprovementOpportunity::class => ImprovementOpportunityPolicy::class,
        AdvisorProfile::class => \App\Policies\AdvisorProfilePolicy::class,
        Panel::class => \App\Policies\PanelPolicy::class,
        Area::class => CatalogPolicy::class,
        Modalidad::class => CatalogPolicy::class,
        Sede::class => CatalogPolicy::class,
        Campus::class => CatalogPolicy::class,
        PeriodoAcademico::class => CatalogPolicy::class,
        TipoEvidencia::class => CatalogPolicy::class,
        Bloque::class => CatalogPolicy::class,
        RequisitoModalidad::class => CatalogPolicy::class,
        Update::class => \App\Policies\UpdatePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Allow policy auto-discovery to keep CvDocumentPolicy working.
        Gate::guessPolicyNamesUsing(function (string $modelClass) {
            return 'App\\Policies\\'.class_basename($modelClass).'Policy';
        });
    }
}
