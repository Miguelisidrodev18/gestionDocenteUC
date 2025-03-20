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
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
