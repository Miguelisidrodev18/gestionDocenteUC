<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modalidades', function (Blueprint $table) {
            if (! Schema::hasColumn('modalidades', 'estructura_duracion')) {
                $table->enum('estructura_duracion', ['CONTINUA', 'BLOQUES'])->default('CONTINUA')->after('duracion_semanas');
            }
            if (! Schema::hasColumn('modalidades', 'num_bloques')) {
                $table->unsignedTinyInteger('num_bloques')->nullable()->after('estructura_duracion');
            }
            if (! Schema::hasColumn('modalidades', 'semanas_por_bloque')) {
                $table->unsignedTinyInteger('semanas_por_bloque')->nullable()->after('num_bloques');
            }
        });

        Schema::table('requisitos_modalidad', function (Blueprint $table) {
            if (! Schema::hasColumn('requisitos_modalidad', 'aplica_a')) {
                $table->enum('aplica_a', ['CICLO', 'POR_BLOQUE'])->default('CICLO')->after('tipo_evidencia_id');
            }
        });

        // Normalizar modalidad semipresencial existente (si aplica)
        DB::table('modalidades')
            ->where('nombre', 'like', '%semi%')
            ->update([
                'estructura_duracion' => 'BLOQUES',
                'num_bloques' => 2,
                'semanas_por_bloque' => 8,
                'duracion_semanas' => 16,
            ]);

        // Completar defaults para el resto
        DB::table('modalidades')
            ->whereNull('estructura_duracion')
            ->update([
                'estructura_duracion' => 'CONTINUA',
                'num_bloques' => null,
                'semanas_por_bloque' => null,
                'duracion_semanas' => 16,
            ]);
    }

    public function down(): void
    {
        Schema::table('requisitos_modalidad', function (Blueprint $table) {
            if (Schema::hasColumn('requisitos_modalidad', 'aplica_a')) {
                $table->dropColumn('aplica_a');
            }
        });

        Schema::table('modalidades', function (Blueprint $table) {
            if (Schema::hasColumn('modalidades', 'semanas_por_bloque')) {
                $table->dropColumn('semanas_por_bloque');
            }
            if (Schema::hasColumn('modalidades', 'num_bloques')) {
                $table->dropColumn('num_bloques');
            }
            if (Schema::hasColumn('modalidades', 'estructura_duracion')) {
                $table->dropColumn('estructura_duracion');
            }
        });
    }
};
