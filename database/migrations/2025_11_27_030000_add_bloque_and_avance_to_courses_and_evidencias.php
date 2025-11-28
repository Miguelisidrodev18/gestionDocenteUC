<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bloque para evidencias (A/B) y deja semana como está (ya tinyint nullable)
        Schema::table('evidencias', function (Blueprint $table) {
            if (! Schema::hasColumn('evidencias', 'bloque')) {
                $table->enum('bloque', ['A', 'B'])->nullable()->after('tipo');
            }
        });

        // Cache de avance y estado de revisión para cursos
        Schema::table('cursos', function (Blueprint $table) {
            if (! Schema::hasColumn('cursos', 'avance_cache')) {
                $table->unsignedTinyInteger('avance_cache')->default(0)->after('periodo_academico');
            }
            if (! Schema::hasColumn('cursos', 'review_state')) {
                $table->enum('review_state', ['pendiente', 'observado', 'validado'])
                    ->default('pendiente')
                    ->after('avance_cache');
            }
        });
    }

    public function down(): void
    {
        Schema::table('evidencias', function (Blueprint $table) {
            if (Schema::hasColumn('evidencias', 'bloque')) {
                $table->dropColumn('bloque');
            }
        });

        Schema::table('cursos', function (Blueprint $table) {
            if (Schema::hasColumn('cursos', 'review_state')) {
                $table->dropColumn('review_state');
            }
            if (Schema::hasColumn('cursos', 'avance_cache')) {
                $table->dropColumn('avance_cache');
            }
        });
    }
};

