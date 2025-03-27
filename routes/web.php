<?php

use App\Http\Controllers\DocenteController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/docentes',[DocenteController::class,'index'])->name('docentes.index');
    Route::get('/docentes/create',[DocenteController::class,'create'])->name('docentes.create');
    Route::post('/docentes',[DocenteController::class,'store'])->name('docentes.store');
    Route::get('/docentes/{docente}/edit',[DocenteController::class,'edit'])->name('docentes.edit');
    Route::put('/docentes/{docente}',[DocenteController::class,'update'])->name('docentes.update');
    Route::delete('/docentes/{docente}',[DocenteController::class,'destroy'])->name('docentes.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/cursos',[CursoController::class,'index'])->name('cursos.index');
    Route::get('/cursos/create',[CursoController::class,'create'])->name('cursos.create');
    Route::post('/cursos',[CursoController::class,'store'])->name('cursos.store');
    Route::get('/cursos/{curso}/edit',[CursoController::class,'edit'])->name('cursos.edit');
    Route::put('/cursos/{curso}',[CursoController::class,'update'])->name('cursos.update');
    Route::delete('/cursos/{curso}',[CursoController::class,'destroy'])->name('cursos.destroy');
});
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/horarios',[HorarioController::class,'index'])->name('horarios.index');
    Route::get('/horarios/create',[HorarioController::class,'create'])->name('horarios.create');
    Route::post('/horarios',[HorarioController::class,'store'])->name('horarios.store');
    Route::get('/horarios/{horario}/edit',[HorarioController::class,'edit'])->name('horarios.edit');
    Route::put('/horarios/{horario}',[HorarioController::class,'update'])->name('horarios.update');
    Route::delete('/horarios/{horario}',[HorarioController::class,'destroy'])->name('horarios.destroy');
});

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
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
