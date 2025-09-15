<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Curso;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $docentesCount = Docente::count();
        $cursosCount = Curso::count();
        $cursosPorPeriodo = Curso::select('periodo', \DB::raw('count(*) as total'))
            ->groupBy('periodo')
            ->get();

        return Inertia::render('Dashboard/Index', [
            'docentesCount' => $docentesCount,
            'cursosCount' => $cursosCount,
            'cursosPorPeriodo' => $cursosPorPeriodo,
        ]);
    }
}
