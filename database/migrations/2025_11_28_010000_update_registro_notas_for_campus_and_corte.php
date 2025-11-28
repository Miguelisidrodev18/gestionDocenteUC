<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_notas', function (Blueprint $table) {
            if (! Schema::hasColumn('registro_notas', 'campus_id')) {
                $table->unsignedBigInteger('campus_id')->nullable()->after('campus');
            }
            if (! Schema::hasColumn('registro_notas', 'corte')) {
                $table->enum('corte', ['PARCIAL', 'CONSOLIDADO2', 'FINAL'])->default('FINAL')->after('nrc');
            }

            // Índice único por curso/campus/nrc/corte
            $table->unique(['curso_id', 'campus_id', 'nrc', 'corte'], 'registro_notas_unique_curso_campus_nrc_corte');
        });
    }

    public function down(): void
    {
        Schema::table('registro_notas', function (Blueprint $table) {
            if (Schema::hasColumn('registro_notas', 'corte')) {
                $table->dropUnique('registro_notas_unique_curso_campus_nrc_corte');
                $table->dropColumn('corte');
            }
            if (Schema::hasColumn('registro_notas', 'campus_id')) {
                $table->dropColumn('campus_id');
            }
        });
    }
};
