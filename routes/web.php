<?php

use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\CourseDocumentController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\EvidenciaController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');


// Dashboard (autenticados)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
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
    Route::patch('/cursos/documents/{document}', [ChecklistController::class, 'update'])->name('cursos.documents.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/docents',[DocenteController::class,'index'])->name('teachers.index');
    Route::get('/docents/create',[DocenteController::class,'create'])->name('docents.create');
    Route::post('/docents',[DocenteController::class,'store'])->name('teachers.store');
    Route::get('/docents/{docent}/edit',[DocenteController::class,'edit'])->name('teachers.edit');
    Route::put('/docents/{docent}',[DocenteController::class,'update'])->name('teachers.update');
    Route::delete('/docents/{docent}',[DocenteController::class,'destroy'])->name('teachers.destroy');
});

// Cursos: index/show para todos los autenticados
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/cursos',[CursoController::class,'index'])->name('cursos.index');
    Route::get('/cursos/{id}', [CursoController::class,'show'])->name('cursos.show');
    // JSON: cursos de un docente por periodo
    Route::get('/docentes/{docente}/cursos', [CursoController::class, 'forDocente'])->name('docentes.cursos');
});

// Crear/editar/eliminar cursos: restringido a admin o responsable
Route::middleware(['auth', 'verified', 'role:responsable,admin'])->group(function () {
    Route::get('/cursos/create',[CursoController::class,'create'])->name('cursos.create');
    Route::post('/cursos',[CursoController::class,'store'])->name('cursos.store');
    Route::get('/cursos/{id}/edit',[CursoController::class,'edit'])->name('cursos.edit');
    Route::put('/cursos/{id}', [CursoController::class, 'update'])->name('cursos.update');
    Route::delete('/cursos/{id}', [CursoController::class, 'destroy'])->name('cursos.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Calendario y reuniones de cursos
    Route::get('/horarios', [MeetingController::class, 'index'])->name('horarios.index');
    Route::post('/horarios/meetings', [MeetingController::class, 'store'])->name('meetings.store');
    Route::put('/horarios/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
    Route::delete('/horarios/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    // Avance rápido de un curso (para UI)
    Route::get('/cursos/{curso}/progreso', [EvidenciaController::class, 'progress'])->name('cursos.progreso');
});
/*
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/actualizaciones',[ActualizacionController::class,'index'])->name('actualizaciones.index');
    Route::get('/actualizaciones/create',[ActualizacionController::class,'create'])->name('actualizaciones.create');
    Route::post('/actualizaciones',[ActualizacionController::class,'store'])->name('actualizaciones.store');
    Route::get('/actualizaciones/{actualizacion}/edit',[ActualizacionController::class,'edit'])->name('actualizaciones.edit');
    Route::put('/actualizaciones/{actualizacion}',[ActualizacionController::class,'update'])->name('actualizaciones.update');
    Route::delete('/actualizaciones/{actualizacion}',[ActualizacionController::class,'destroy'])->name('actualizaciones.destroy');
});
Route::middleware(['auth','verified'])->group(function () {
    Route::get('/materiales', [MaterialController::class,'index'])->name('materiales.index');  
    Route::get('/materiales/create',[MaterialController::class,'create'])->name('materiales.create');
    Route::post('/materiales',[MaterialController::class,'store'])->name('materiales.store');
    Route::get('/materiales/{material}/edit',[MaterialController::class,'edit'])->name('materiales.edit');
    Route::put('/materiales/{material}',[MaterialController::class,'update'])->name('materiales.update');
    Route::delete('/materiales/{material}',[MaterialController::class,'destroy'])->name('materiales.destroy'); 
});
*/
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
