<?php

use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\CourseDocumentController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\EvidenciaController;
use App\Http\Controllers\CVController;
use App\Http\Controllers\FinalController;
use App\Http\Controllers\ResponsibilityController;
use App\Http\Controllers\AdvisorPanelController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Dashboard (autenticados)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/metrics', [DashboardController::class, 'metrics'])->name('dashboard.metrics');
});

// Subida de documentos de cursos (docente o responsable). Admin también pasa por el middleware
Route::middleware(['auth', 'verified', 'role:docente,responsable'])->group(function () {
    Route::post('/cursos/{curso}/documents', [CourseDocumentController::class, 'store'])->name('cursos.documents.store');
    Route::post('/cursos/{curso}/evidencias', [EvidenciaController::class, 'store'])->name('cursos.evidencias.store');
    // Actas (formulario en vez de archivo)
    Route::post('/cursos/{curso}/actas', [\App\Http\Controllers\ActaController::class, 'store'])->name('cursos.actas.store');
    Route::patch('/actas/{acta}', [\App\Http\Controllers\ActaController::class, 'update'])->name('actas.update');
    Route::delete('/actas/{acta}', [\App\Http\Controllers\ActaController::class, 'destroy'])->name('actas.destroy');
    // Registro de notas e Informe Final
    Route::post('/cursos/{curso}/registro-notas', [\App\Http\Controllers\RegistroNotasController::class, 'store'])->name('cursos.registro.store');
    Route::delete('/registro-notas/{registroNota}', [\App\Http\Controllers\RegistroNotasController::class, 'destroy'])->name('cursos.registro.destroy');
    Route::post('/cursos/{curso}/informe-final', [\App\Http\Controllers\InformeFinalController::class, 'store'])->name('cursos.informe_final.store');
    Route::get('/cursos/{curso}/informe-final/preview', [\App\Http\Controllers\InformeFinalController::class, 'preview'])->name('cursos.informe_final.preview');
    Route::delete('/evidencias/{evidencia}', [EvidenciaController::class, 'destroy'])->name('evidencias.destroy');
    Route::patch('/evidencias/{evidencia}', [EvidenciaController::class, 'update'])->name('evidencias.update');
});

// Checklist de cursos (solo responsables; admin permitido por el middleware personalizado)
Route::middleware(['auth', 'verified', 'role:responsable'])->group(function () {
    Route::get('/cursos/checklist', [ChecklistController::class, 'index'])->name('cursos.checklist');
    // La actualización manual del checklist está deshabilitada; la ruta se mantiene por compatibilidad.
    Route::patch('/cursos/documents/{document}', [ChecklistController::class, 'update'])->name('cursos.documents.update');
    Route::patch('/cursos/{curso}/review-state', [ChecklistController::class, 'changeCourseState'])->name('cursos.review_state');
});

// Docentes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/docents', [DocenteController::class, 'index'])->name('teachers.index');
    Route::get('/docents/create', [DocenteController::class, 'create'])->name('docents.create');
    Route::post('/docents', [DocenteController::class, 'store'])->name('teachers.store');
    Route::get('/docents/{docent}/edit', [DocenteController::class, 'edit'])->name('teachers.edit');
    Route::put('/docents/{docent}', [DocenteController::class, 'update'])->name('teachers.update');
    Route::delete('/docents/{docent}', [DocenteController::class, 'destroy'])->name('teachers.destroy');
});

// Cursos: index/show para todos los autenticados
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/cursos', [CursoController::class, 'index'])->name('cursos.index');
    Route::get('/cursos/{id}', [CursoController::class, 'show'])->name('cursos.show');
    // JSON: cursos de un docente por periodo
    Route::get('/docentes/{docente}/cursos', [CursoController::class, 'forDocente'])->name('docentes.cursos');
});

// Crear/editar/eliminar cursos: restringido a admin o responsable
Route::middleware(['auth', 'verified', 'role:responsable,admin'])->group(function () {
    // Módulo responsabilidades reemplaza la antigua vista de asignaciones
    Route::get('/responsabilidades', [ResponsibilityController::class, 'index'])->name('responsabilidades.index');
    Route::patch('/responsabilidades/{curso}', [ResponsibilityController::class, 'update'])->name('responsabilidades.update');

    Route::post('/cursos/traer', [CursoController::class, 'importFromPrevious'])->name('cursos.import_previous');
    // Ruta antigua de actualización puntual del responsable (se mantiene por compatibilidad con otras vistas)
    Route::patch('/cursos/{curso}/docente-responsable', [CursoController::class, 'updateResponsableDocente'])->name('cursos.responsable.update');
    Route::get('/cursos/create', [CursoController::class, 'create'])->name('cursos.create');
    Route::post('/cursos', [CursoController::class, 'store'])->name('cursos.store');
    Route::get('/cursos/{id}/edit', [CursoController::class, 'edit'])->name('cursos.edit');
    Route::put('/cursos/{id}', [CursoController::class, 'update'])->name('cursos.update');
    Route::delete('/cursos/{id}', [CursoController::class, 'destroy'])->name('cursos.destroy');

    // Módulo Final (resultados agregados y oportunidades de mejora)
    Route::get('/final', [FinalController::class, 'index'])->name('final.index');
    Route::post('/final/opportunities', [FinalController::class, 'storeOpportunity'])->name('final.opportunities.store');
    Route::put('/final/opportunities/{opportunity}', [FinalController::class, 'updateOpportunity'])->name('final.opportunities.update');
    Route::delete('/final/opportunities/{opportunity}', [FinalController::class, 'destroyOpportunity'])->name('final.opportunities.destroy');
});

// Calendario, notificaciones, avance rápido y Asesores/Jurados
Route::middleware(['auth', 'verified'])->group(function () {
    // Calendario y reuniones de cursos
    Route::get('/horarios', [MeetingController::class, 'index'])->name('horarios.index');
    Route::post('/horarios/meetings', [MeetingController::class, 'store'])->name('meetings.store');
    Route::put('/horarios/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
    Route::delete('/horarios/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');

    // Notificaciones
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // Avance rápido de un curso (para UI)
    Route::get('/cursos/{curso}/progreso', [EvidenciaController::class, 'progress'])->name('cursos.progreso');

    // Asesores y Jurados
    Route::get('/asesores-jurados', [AdvisorPanelController::class, 'index'])->name('advisors.index');
    Route::post('/panels', [AdvisorPanelController::class, 'storePanel'])->middleware('role:responsable,admin');
    Route::post('/assignments', [AdvisorPanelController::class, 'storeAssignment'])->middleware('role:responsable,admin');
    Route::put('/assignments/{assignment}/aceptar', [AdvisorPanelController::class, 'acceptAssignment'])->name('assignments.accept');
    Route::put('/assignments/{assignment}/rechazar', [AdvisorPanelController::class, 'rejectAssignment'])->name('assignments.reject');
});

// Catálogos (datos maestros)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/catalogos', [CatalogController::class, 'index'])->name('catalogos.index');

    Route::post('/catalogos/sedes', [CatalogController::class, 'storeSede']);
    Route::put('/catalogos/sedes/{sede}', [CatalogController::class, 'updateSede']);
    Route::delete('/catalogos/sedes/{sede}', [CatalogController::class, 'destroySede']);

    Route::post('/catalogos/campus', [CatalogController::class, 'storeCampus']);
    Route::put('/catalogos/campus/{campus}', [CatalogController::class, 'updateCampus']);
    Route::delete('/catalogos/campus/{campus}', [CatalogController::class, 'destroyCampus']);

    Route::post('/catalogos/areas', [CatalogController::class, 'storeArea']);
    Route::put('/catalogos/areas/{area}', [CatalogController::class, 'updateArea']);
    Route::delete('/catalogos/areas/{area}', [CatalogController::class, 'destroyArea']);

    Route::post('/catalogos/modalidades', [CatalogController::class, 'storeModalidad']);
    Route::put('/catalogos/modalidades/{modalidad}', [CatalogController::class, 'updateModalidad']);
    Route::delete('/catalogos/modalidades/{modalidad}', [CatalogController::class, 'destroyModalidad']);

    Route::post('/catalogos/periodos', [CatalogController::class, 'storePeriodo']);
    Route::put('/catalogos/periodos/{periodo}', [CatalogController::class, 'updatePeriodo']);
    Route::delete('/catalogos/periodos/{periodo}', [CatalogController::class, 'destroyPeriodo']);

    Route::post('/catalogos/tipos-evidencia', [CatalogController::class, 'storeTipoEvidencia']);
    Route::put('/catalogos/tipos-evidencia/{tipo}', [CatalogController::class, 'updateTipoEvidencia']);
    Route::delete('/catalogos/tipos-evidencia/{tipo}', [CatalogController::class, 'destroyTipoEvidencia']);

    Route::post('/catalogos/bloques', [CatalogController::class, 'storeBloque']);
    Route::put('/catalogos/bloques/{bloque}', [CatalogController::class, 'updateBloque']);
    Route::delete('/catalogos/bloques/{bloque}', [CatalogController::class, 'destroyBloque']);

    Route::post('/catalogos/requisitos', [CatalogController::class, 'storeRequisito']);
    Route::put('/catalogos/requisitos/{requisito}', [CatalogController::class, 'updateRequisito']);
    Route::delete('/catalogos/requisitos/{requisito}', [CatalogController::class, 'destroyRequisito']);
});

// Flujo de CV Docente (plantilla, generación automática y subida de versiones firmadas)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/docentes/cv/plantilla', [CVController::class, 'downloadTemplate'])->name('cv.plantilla');
    Route::get('/docentes/{docente}/cv/generar', [CVController::class, 'generateFilled'])->name('cv.generar');
    Route::post('/docentes/{docente}/cv/upload', [CVController::class, 'upload'])->name('cv.upload');
    Route::get('/docentes/{docente}/cv/descargar/{cv}', [CVController::class, 'download'])->name('cv.download');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
