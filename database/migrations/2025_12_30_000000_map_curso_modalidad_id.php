<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $modalidades = DB::table('modalidades')->select('id', 'nombre')->get();
        if ($modalidades->isEmpty()) {
            return;
        }

        $map = [];
        foreach ($modalidades as $modalidad) {
            $key = strtolower(trim((string) $modalidad->nombre));
            if ($key === '') {
                continue;
            }
            $map[$key] = $modalidad->id;
        }

        if (! $map) {
            return;
        }

        $cursos = DB::table('cursos')
            ->select('id', 'modalidad', 'modalidad_id')
            ->whereNull('modalidad_id')
            ->whereNotNull('modalidad')
            ->get();

        foreach ($cursos as $curso) {
            $key = strtolower(trim((string) $curso->modalidad));
            if ($key === '' || ! isset($map[$key])) {
                continue;
            }
            DB::table('cursos')->where('id', $curso->id)->update([
                'modalidad_id' => $map[$key],
            ]);
        }
    }

    public function down(): void
    {
        // No-op: mantener modalidad_id mapeada.
    }
};
