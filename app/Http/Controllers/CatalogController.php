<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bloque;
use App\Models\Campus;
use App\Models\Modalidad;
use App\Models\PeriodoAcademico;
use App\Models\RequisitoModalidad;
use App\Models\Sede;
use App\Models\TipoEvidencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CatalogController extends Controller
{
    public function index()
    {
        $catalogs = Cache::remember('catalogs.all', 1800, function () {
            return [
                'sedes' => Sede::orderBy('nombre')->get(),
                'campus' => Campus::with('sede')->orderBy('nombre')->get(),
                'areas' => Area::orderBy('nombre')->get(),
                'modalidades' => Modalidad::with('area')->orderBy('nombre')->get(),
                'periodos' => PeriodoAcademico::orderByDesc('codigo')->get(),
                'tipos_evidencia' => TipoEvidencia::orderBy('codigo')->get(),
                'bloques' => Bloque::orderBy('codigo')->get(),
                'requisitos' => RequisitoModalidad::with(['modalidad', 'tipo', 'bloque'])->get(),
            ];
        });

        return Inertia::render('Catalogos/Index', $catalogs);
    }

    protected function flushCache(): void
    {
        Cache::forget('catalogs.all');
    }

    // SEDES
    public function storeSede(Request $request)
    {
        $this->authorize('create', Sede::class);
        $data = $request->validate([
            'codigo' => 'required|string|max:50|unique:sedes,codigo',
            'nombre' => 'required|string|max:150',
            'activo' => 'boolean',
        ]);

        Sede::create($data);
        $this->flushCache();

        return back()->with('success', 'Sede creada');
    }

    public function updateSede(Request $request, Sede $sede)
    {
        $this->authorize('update', $sede);
        $data = $request->validate([
            'codigo' => 'required|string|max:50|unique:sedes,codigo,'.$sede->id,
            'nombre' => 'required|string|max:150',
            'activo' => 'boolean',
        ]);

        $sede->update($data);
        $this->flushCache();

        return back()->with('success', 'Sede actualizada');
    }

    public function destroySede(Sede $sede)
    {
        $this->authorize('delete', $sede);
        $sede->delete();
        $this->flushCache();

        return back()->with('success', 'Sede eliminada');
    }

    // CAMPUS
    public function storeCampus(Request $request)
    {
        $this->authorize('create', Campus::class);
        $data = $request->validate([
            'sede_id' => 'required|exists:sedes,id',
            'codigo' => 'required|string|max:50|unique:campus,codigo',
            'nombre' => 'required|string|max:150',
            'activo' => 'boolean',
        ]);

        Campus::create($data);
        $this->flushCache();

        return back()->with('success', 'Campus creado');
    }

    public function updateCampus(Request $request, Campus $campus)
    {
        $this->authorize('update', $campus);
        $data = $request->validate([
            'sede_id' => 'required|exists:sedes,id',
            'codigo' => 'required|string|max:50|unique:campus,codigo,'.$campus->id,
            'nombre' => 'required|string|max:150',
            'activo' => 'boolean',
        ]);

        $campus->update($data);
        $this->flushCache();

        return back()->with('success', 'Campus actualizado');
    }

    public function destroyCampus(Campus $campus)
    {
        $this->authorize('delete', $campus);
        $campus->delete();
        $this->flushCache();

        return back()->with('success', 'Campus eliminado');
    }

    // ÁREAS
    public function storeArea(Request $request)
    {
        $this->authorize('create', Area::class);
        $data = $request->validate([
            'codigo' => 'required|string|max:50|unique:areas,codigo',
            'nombre' => 'required|string|max:150',
            'activo' => 'boolean',
        ]);

        Area::create($data);
        $this->flushCache();

        return back()->with('success', 'Área creada');
    }

    public function updateArea(Request $request, Area $area)
    {
        $this->authorize('update', $area);
        $data = $request->validate([
            'codigo' => 'required|string|max:50|unique:areas,codigo,'.$area->id,
            'nombre' => 'required|string|max:150',
            'activo' => 'boolean',
        ]);

        $area->update($data);
        $this->flushCache();

        return back()->with('success', 'Área actualizada');
    }

    public function destroyArea(Area $area)
    {
        $this->authorize('delete', $area);
        $area->delete();
        $this->flushCache();

        return back()->with('success', 'Área eliminada');
    }

    // MODALIDADES
    public function storeModalidad(Request $request)
    {
        $this->authorize('create', Modalidad::class);
        $data = $request->validate([
            'codigo' => 'required|string|max:50|unique:modalidades,codigo',
            'nombre' => 'required|string|max:150',
            'duracion_semanas' => 'required|integer|min:1|max:32',
            'area_id' => 'required|exists:areas,id',
            'activo' => 'boolean',
        ]);

        Modalidad::create($data);
        $this->flushCache();

        return back()->with('success', 'Modalidad creada');
    }

    public function updateModalidad(Request $request, Modalidad $modalidad)
    {
        $this->authorize('update', $modalidad);
        $data = $request->validate([
            'codigo' => 'required|string|max:50|unique:modalidades,codigo,'.$modalidad->id,
            'nombre' => 'required|string|max:150',
            'duracion_semanas' => 'required|integer|min:1|max:32',
            'area_id' => 'required|exists:areas,id',
            'activo' => 'boolean',
        ]);

        $modalidad->update($data);
        $this->flushCache();

        return back()->with('success', 'Modalidad actualizada');
    }

    public function destroyModalidad(Modalidad $modalidad)
    {
        $this->authorize('delete', $modalidad);
        $modalidad->delete();
        $this->flushCache();

        return back()->with('success', 'Modalidad eliminada');
    }

    // PERIODOS ACADÉMICOS
    public function storePeriodo(Request $request)
    {
        $this->authorize('create', PeriodoAcademico::class);
        $data = $request->validate([
            'codigo' => 'required|string|max:50|unique:periodos_academicos,codigo',
            'inicio' => 'nullable|date',
            'fin' => 'nullable|date|after_or_equal:inicio',
            'estado' => 'required|in:ABIERTO,CERRADO',
        ]);

        PeriodoAcademico::create($data);
        $this->flushCache();

        return back()->with('success', 'Periodo creado');
    }

    public function updatePeriodo(Request $request, PeriodoAcademico $periodo)
    {
        $this->authorize('update', $periodo);
        $data = $request->validate([
            'codigo' => 'required|string|max:50|unique:periodos_academicos,codigo,'.$periodo->id,
            'inicio' => 'nullable|date',
            'fin' => 'nullable|date|after_or_equal:inicio',
            'estado' => 'required|in:ABIERTO,CERRADO',
        ]);

        $periodo->update($data);
        $this->flushCache();

        return back()->with('success', 'Periodo actualizado');
    }

    public function destroyPeriodo(PeriodoAcademico $periodo)
    {
        $this->authorize('delete', $periodo);
        $periodo->delete();
        $this->flushCache();

        return back()->with('success', 'Periodo eliminado');
    }

    // TIPOS EVIDENCIA
    public function storeTipoEvidencia(Request $request)
    {
        $this->authorize('create', TipoEvidencia::class);
        $data = $request->validate([
            'codigo' => 'required|string|max:50|unique:tipos_evidencia,codigo',
            'nombre' => 'required|string|max:150',
            'cuenta_en_avance' => 'boolean',
            'activo' => 'boolean',
        ]);

        TipoEvidencia::create($data);
        $this->flushCache();

        return back()->with('success', 'Tipo de evidencia creado');
    }

    public function updateTipoEvidencia(Request $request, TipoEvidencia $tipo)
    {
        $this->authorize('update', $tipo);
        $data = $request->validate([
            'codigo' => 'required|string|max:50|unique:tipos_evidencia,codigo,'.$tipo->id,
            'nombre' => 'required|string|max:150',
            'cuenta_en_avance' => 'boolean',
            'activo' => 'boolean',
        ]);

        $tipo->update($data);
        $this->flushCache();

        return back()->with('success', 'Tipo de evidencia actualizado');
    }

    public function destroyTipoEvidencia(TipoEvidencia $tipo)
    {
        $this->authorize('delete', $tipo);
        $tipo->delete();
        $this->flushCache();

        return back()->with('success', 'Tipo de evidencia eliminado');
    }

    // BLOQUES
    public function storeBloque(Request $request)
    {
        $this->authorize('create', Bloque::class);
        $data = $request->validate([
            'codigo' => 'required|string|max:50|unique:bloques,codigo',
            'nombre' => 'required|string|max:150',
            'semanas' => 'required|integer|min:1|max:20',
            'activo' => 'boolean',
        ]);

        Bloque::create($data);
        $this->flushCache();

        return back()->with('success', 'Bloque creado');
    }

    public function updateBloque(Request $request, Bloque $bloque)
    {
        $this->authorize('update', $bloque);
        $data = $request->validate([
            'codigo' => 'required|string|max:50|unique:bloques,codigo,'.$bloque->id,
            'nombre' => 'required|string|max:150',
            'semanas' => 'required|integer|min:1|max:20',
            'activo' => 'boolean',
        ]);

        $bloque->update($data);
        $this->flushCache();

        return back()->with('success', 'Bloque actualizado');
    }

    public function destroyBloque(Bloque $bloque)
    {
        $this->authorize('delete', $bloque);
        $bloque->delete();
        $this->flushCache();

        return back()->with('success', 'Bloque eliminado');
    }

    // REQUISITOS MODALIDAD
    public function storeRequisito(Request $request)
    {
        $this->authorize('create', RequisitoModalidad::class);
        $data = $request->validate([
            'modalidad_id' => 'required|exists:modalidades,id',
            'tipo_evidencia_id' => 'required|exists:tipos_evidencia,id',
            'bloque_id' => 'nullable|exists:bloques,id',
            'minimo' => 'required|integer|min:0',
            'peso' => 'required|integer|min:0|max:100',
        ]);

        RequisitoModalidad::create($data);
        $this->flushCache();

        return back()->with('success', 'Requisito creado');
    }

    public function updateRequisito(Request $request, RequisitoModalidad $requisito)
    {
        $this->authorize('update', $requisito);
        $data = $request->validate([
            'modalidad_id' => 'required|exists:modalidades,id',
            'tipo_evidencia_id' => 'required|exists:tipos_evidencia,id',
            'bloque_id' => 'nullable|exists:bloques,id',
            'minimo' => 'required|integer|min:0',
            'peso' => 'required|integer|min:0|max:100',
        ]);

        $requisito->update($data);
        $this->flushCache();

        return back()->with('success', 'Requisito actualizado');
    }

    public function destroyRequisito(RequisitoModalidad $requisito)
    {
        $this->authorize('delete', $requisito);
        $requisito->delete();
        $this->flushCache();

        return back()->with('success', 'Requisito eliminado');
    }
}

