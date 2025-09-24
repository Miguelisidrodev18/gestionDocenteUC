<?php

use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\CourseDocumentController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\HorarioController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');


// Rutas para administrador (acceso completo)
Route::middleware(['role:admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/docentes', [DocenteController::class, 'index'])->name('docentes.index');
    Route::get('/cursos/checklist', [ChecklistController::class, 'index'])->name('cursos.checklist');
});

// Rutas para docente responsable (también puede subir archivos)
Route::middleware(['role:responsable', 'role:docente'])->group(function () {
    Route::get('/cursos/checklist', [ChecklistController::class, 'index'])->name('cursos.checklist');
    Route::post('/cursos/upload', [ChecklistController::class, 'upload'])->name('cursos.upload');
});

// Rutas para docentes (solo suben archivos)
Route::middleware(['role:docente'])->group(function () {
    Route::post('/cursos/upload', [ChecklistController::class, 'upload'])->name('cursos.upload');
});


Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:docente,responsable'])->group(function () {
    Route::get('/docentes', [DocenteController::class, 'index'])->name('docentes.index');
    Route::post('/cursos/{curso}/documents', [CourseDocumentController::class, 'store'])->name('cursos.documents.store');
});

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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/cursos',[CursoController::class,'index'])->name('cursos.index');
    Route::get('/cursos/create',[CursoController::class,'create'])->name('cursos.create');
    Route::post('/cursos',[CursoController::class,'store'])->name('cursos.store');
    Route::get('/cursos/{id}/edit',[CursoController::class,'edit'])->name('cursos.edit');
    Route::put('/cursos/{id}', [CursoController::class, 'update'])->name('cursos.update');
    Route::delete('/cursos/{id}', [CursoController::class, 'destroy'])->name('cursos.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/horarios',[HorarioController::class,'index'])->name('horarios.index');
    Route::get('/horarios/create',[HorarioController::class,'create'])->name('horarios.create');
    Route::post('/horarios',[HorarioController::class,'store'])->name('horarios.store');
    Route::get('/horarios/{horario}/edit',[HorarioController::class,'edit'])->name('horarios.edit');
    Route::put('/horarios/{horario}',[HorarioController::class,'update'])->name('horarios.update');
    Route::delete('/horarios/{horario}',[HorarioController::class,'destroy'])->name('horarios.destroy');
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
