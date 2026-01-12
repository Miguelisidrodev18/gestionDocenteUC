<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Modalidad;
use App\Models\Bloque;
use App\Models\Especialidad;
use App\Models\PeriodoAcademico;
use App\Models\RequisitoModalidad;
use App\Models\Sede;
use App\Models\TipoEvidencia;
use App\Models\Curso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CatalogController extends Controller
{
    public function index()
    {
        $catalogs = Cache::remember('catalogs.all', 1800, function () {
            return [
                'sedes' => Sede::orderBy('nombre')->get(),
                'areas' => Area::orderBy('nombre')->get(),
                'modalidades' => Modalidad::with(['area', 'bloque'])->orderBy('nombre')->get(),
                'periodos' => PeriodoAcademico::orderByDesc('codigo')->get(),
                'tipos_evidencia' => TipoEvidencia::orderBy('codigo')->get(),
                'bloques' => Bloque::orderBy('codigo')->get(),
                'requisitos' => RequisitoModalidad::with(['modalidad', 'tipo'])->get(),
                'especialidades' => Especialidad::orderBy('nombre')->get(),
            ];
        });

        return Inertia::render('Catalogos/Index', $catalogs);
    }

    protected function flushCache(): void
    {
        Cache::forget('catalogs.all');
    }

    protected function normalizeNombre(string $nombre): string
    {
        return strtolower(trim($nombre));
    }

    protected function assertNombrePermitido(string $nombre): void
    {
        $normalized = $this->normalizeNombre($nombre);
        if (
            ! str_starts_with($normalized, 'presencial') &&
            ! str_starts_with($normalized, 'semipresencial')
        ) {
            throw ValidationException::withMessages([
                'nombre' => 'Solo se permiten modalidades Presencial o Semipresencial.',
            ]);
        }
    }

    protected function assertNombreUnico(string $nombre, ?int $areaId = null, ?int $ignoreId = null): void
    {
        $normalized = $this->normalizeNombre($nombre);
        $query = Modalidad::query()->whereRaw('LOWER(TRIM(nombre)) = ?', [$normalized]);
        if ($areaId) {
            $query->where('area_id', $areaId);
        }
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages([
                'nombre' => 'Ya existe una modalidad con ese nombre.',
            ]);
        }
    }

    protected function generarCodigoModalidad(string $nombre, ?int $ignoreId = null): string
    {
        $base = strtoupper(Str::slug($nombre, '_'));
        if ($base === '') {
            $base = 'MODALIDAD';
        }
        $codigo = $base;
        $suffix = 2;
        while (Modalidad::where('codigo', $codigo)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $codigo = $base.'_'.$suffix;
            $suffix++;
        }
        return $codigo;
    }

    protected function generarCodigoCatalogo(string $nombre, string $modelClass, ?int $ignoreId = null): string
    {
        $base = strtoupper(Str::slug($nombre, '_'));
        if ($base === '') {
            $base = 'CODIGO';
        }
        $codigo = $base;
        $suffix = 2;
        while ($modelClass::where('codigo', $codigo)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $codigo = $base.'_'.$suffix;
            $suffix++;
        }
        return $codigo;
    }

    // SEDES
    public function storeSede(Request $request)
    {
        $this->authorize('create', Sede::class);
        $data = $request->validate([
            'codigo' => 'nullable|string|max:50|unique:sedes,codigo',
            'nombre' => 'required|string|max:150',
            'activo' => 'boolean',
        ]);
        $codigo = trim((string) ($data['codigo'] ?? ''));
        $data['codigo'] = $codigo !== ''
            ? $codigo
            : $this->generarCodigoCatalogo($data['nombre'], Sede::class);


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
        try {
            $sede->forceDelete();
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo eliminar la sede porque tiene registros asociados.');
        }
        $this->flushCache();

        return back()->with('success', 'Sede eliminada');
    }

    // ÁREAS
    public function storeArea(Request $request)
    {
        $this->authorize('create', Area::class);
        $data = $request->validate([
            'codigo' => 'nullable|string|max:50|unique:areas,codigo',
            'nombre' => 'required|string|max:150',
            'activo' => 'boolean',
        ]);
        $codigo = trim((string) ($data['codigo'] ?? ''));
        $data['codigo'] = $codigo !== ''
            ? $codigo
            : $this->generarCodigoCatalogo($data['nombre'], Area::class);


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
        try {
            $area->forceDelete();
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo eliminar el area porque tiene registros asociados.');
        }
        $this->flushCache();

        return back()->with('success', 'Área eliminada');
    }

    // MODALIDADES
    public function storeModalidad(Request $request)
    {
        $this->authorize('create', Modalidad::class);
        $data = $request->validate([
            'nombre' => 'required|string|max:150',
            'duracion_semanas' => 'nullable|integer|min:1|max:32',
            'bloque_id' => 'nullable|exists:bloques,id',
            'area_id' => 'nullable|exists:areas,id',
            'activo' => 'boolean',
        ]);

        $this->assertNombrePermitido($data['nombre']);
        $this->assertNombreUnico($data['nombre'], $data['area_id'] ?? null);

        if (! empty($data['bloque_id'])) {
            $bloque = Bloque::find($data['bloque_id']);
            $semanasPorBloque = $data['duracion_semanas'] ?? $bloque?->semanas ?? 8;
            $data['estructura_duracion'] = 'BLOQUES';
            $data['num_bloques'] = 2;
            $data['semanas_por_bloque'] = $semanasPorBloque;
            $data['duracion_semanas'] = $semanasPorBloque;
        } else {
            $data['estructura_duracion'] = 'CONTINUA';
            $data['num_bloques'] = null;
            $data['semanas_por_bloque'] = null;
            $data['duracion_semanas'] = $data['duracion_semanas'] ?? 16;
            $data['bloque_id'] = null;
        }

        $data['codigo'] = $this->generarCodigoModalidad($data['nombre']);
        $data['area_id'] = $data['area_id'] ?? Area::orderBy('id')->value('id');
        if (! $data['area_id']) {
            throw ValidationException::withMessages([
                'area_id' => 'Debe existir al menos un area para registrar modalidades.',
            ]);
        }

        $data['activo'] = $request->user()?->isAdmin()
            ? $request->boolean('activo', true)
            : true;

        Modalidad::create($data);
        $this->flushCache();

        return back()->with('success', 'Modalidad creada');
    }

    public function updateModalidad(Request $request, Modalidad $modalidad)
    {
        $this->authorize('update', $modalidad);
        $data = $request->validate([
            'nombre' => 'required|string|max:150',
            'duracion_semanas' => 'nullable|integer|min:1|max:32',
            'bloque_id' => 'nullable|exists:bloques,id',
            'area_id' => 'nullable|exists:areas,id',
            'activo' => 'boolean',
        ]);

        $this->assertNombrePermitido($data['nombre']);
        $this->assertNombreUnico($data['nombre'], $data['area_id'] ?? $modalidad->area_id, $modalidad->id);

        if (! empty($data['bloque_id'])) {
            $bloque = Bloque::find($data['bloque_id']);
            $semanasPorBloque = $data['duracion_semanas'] ?? $bloque?->semanas ?? 8;
            $data['estructura_duracion'] = 'BLOQUES';
            $data['num_bloques'] = 2;
            $data['semanas_por_bloque'] = $semanasPorBloque;
            $data['duracion_semanas'] = $semanasPorBloque;
        } else {
            $data['estructura_duracion'] = 'CONTINUA';
            $data['num_bloques'] = null;
            $data['semanas_por_bloque'] = null;
            $data['duracion_semanas'] = $data['duracion_semanas'] ?? 16;
            $data['bloque_id'] = null;
        }

        $data['codigo'] = $this->generarCodigoModalidad($data['nombre'], $modalidad->id);
        $data['area_id'] = $data['area_id'] ?: $modalidad->area_id;

        if (! $request->user()?->isAdmin()) {
            $data['activo'] = $modalidad->activo;
        }

        $modalidad->update($data);
        $this->flushCache();

        return back()->with('success', 'Modalidad actualizada');
    }

    public function destroyModalidad(Modalidad $modalidad)
    {
        $this->authorize('delete', $modalidad);
        try {
            $modalidad->forceDelete();
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo eliminar la modalidad porque tiene registros asociados.');
        }
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
            'codigo' => 'nullable|string|max:50|unique:tipos_evidencia,codigo',
            'nombre' => 'required|string|max:150',
            'cuenta_en_avance' => 'boolean',
            'activo' => 'boolean',
        ]);
        $codigo = trim((string) ($data['codigo'] ?? ''));
        $data['codigo'] = $codigo !== ''
            ? $codigo
            : $this->generarCodigoCatalogo($data['nombre'], TipoEvidencia::class);


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
        try {
            $tipo->forceDelete();
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo eliminar el tipo de evidencia porque tiene registros asociados.');
        }
        $this->flushCache();

        return back()->with('success', 'Tipo de evidencia eliminado');
    }

    // REQUISITOS MODALIDAD
    public function storeRequisito(Request $request)
    {
        $this->authorize('create', RequisitoModalidad::class);
        $data = $request->validate([
            'modalidad_id' => 'required|exists:modalidades,id',
            'tipo_evidencia_id' => 'required|exists:tipos_evidencia,id',
            'aplica_a' => 'required|in:CICLO,POR_BLOQUE',
            'minimo' => 'required|integer|min:0',
            'peso' => 'required|integer|min:0|max:100',
        ]);

        $modalidad = Modalidad::find($data['modalidad_id']);
        if ($modalidad && $data['aplica_a'] === 'POR_BLOQUE' && $modalidad->estructura_duracion !== 'BLOQUES') {
            return back()->withErrors(['aplica_a' => 'POR_BLOQUE solo permitido para modalidades con estructura BLOQUES.']);
        }

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
            'aplica_a' => 'required|in:CICLO,POR_BLOQUE',
            'minimo' => 'required|integer|min:0',
            'peso' => 'required|integer|min:0|max:100',
        ]);

        $modalidad = Modalidad::find($data['modalidad_id']);
        if ($modalidad && $data['aplica_a'] === 'POR_BLOQUE' && $modalidad->estructura_duracion !== 'BLOQUES') {
            return back()->withErrors(['aplica_a' => 'POR_BLOQUE solo permitido para modalidades con estructura BLOQUES.']);
        }

        $requisito->update($data);
        $this->flushCache();

        return back()->with('success', 'Requisito actualizado');
    }

    // BLOQUES
    public function storeBloque(Request $request)
    {
        $this->authorize('create', Bloque::class);
        $data = $request->validate([
            'codigo' => 'nullable|string|max:50|unique:bloques,codigo',
            'nombre' => 'required|string|max:150',
            'semanas' => 'required|integer|min:1|max:20',
            'activo' => 'boolean',
        ]);
        $codigo = trim((string) ($data['codigo'] ?? ''));
        $data['codigo'] = $codigo !== ''
            ? $codigo
            : $this->generarCodigoCatalogo($data['nombre'], Bloque::class);


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
        try {
            $bloque->forceDelete();
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo eliminar el bloque porque tiene registros asociados.');
        }
        $this->flushCache();

        return back()->with('success', 'Bloque eliminado');
    }

    public function cleanCatalogs(Request $request)
    {
        $this->authorize('create', Sede::class);

        try {
            DB::transaction(function () {
                Curso::query()->update([
                    'sede_id' => null,
                    'area_id' => null,
                    'modalidad_id' => null,
                    'periodo_id' => null,
                ]);

                RequisitoModalidad::query()->delete();
                Modalidad::query()->forceDelete();
                Bloque::query()->forceDelete();
                Area::query()->forceDelete();
                Sede::query()->forceDelete();
                PeriodoAcademico::query()->delete();
                TipoEvidencia::query()->forceDelete();
                Especialidad::query()->delete();
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo limpiar catalogos por registros asociados.');
        }

        $this->flushCache();

        return back()->with('success', 'Catalogos limpiados correctamente.');
    }

    public function cleanModalidades(Request $request)
    {
        $this->authorize('create', Modalidad::class);

        try {
            DB::transaction(function () {
                Curso::query()->update([
                    'modalidad_id' => null,
                ]);

                RequisitoModalidad::query()->delete();
                Modalidad::query()->forceDelete();
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo limpiar modalidades por registros asociados.');
        }

        $this->flushCache();

        return back()->with('success', 'Modalidades limpiadas correctamente.');
    }

    public function destroyRequisito(RequisitoModalidad $requisito)
    {
        $this->authorize('delete', $requisito);
        $requisito->delete();
        $this->flushCache();

        return back()->with('success', 'Requisito eliminado');
    }

    // ESPECIALIDADES
    public function storeEspecialidad(Request $request)
    {
        $this->authorize('create', Especialidad::class);
        $data = $request->validate([
            'nombre' => 'required|string|max:150|unique:especialidades,nombre',
        ]);

        Especialidad::create($data);
        $this->flushCache();

        return back()->with('success', 'Especialidad creada');
    }

    public function updateEspecialidad(Request $request, Especialidad $especialidad)
    {
        $this->authorize('update', $especialidad);
        $data = $request->validate([
            'nombre' => 'required|string|max:150|unique:especialidades,nombre,'.$especialidad->id,
        ]);

        $especialidad->update($data);
        $this->flushCache();

        return back()->with('success', 'Especialidad actualizada');
    }

    public function destroyEspecialidad(Especialidad $especialidad)
    {
        $this->authorize('delete', $especialidad);
        $especialidad->delete();
        $this->flushCache();

        return back()->with('success', 'Especialidad eliminada');
    }
}
