<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            // Drop existing foreign key if present and recreate with cascade
            try {
                $table->dropForeign('cursos_docente_id_foreign');
            } catch (\Throwable $e) {
                try { $table->dropForeign(['docente_id']); } catch (\Throwable $e2) {}
            }
        });

        Schema::table('cursos', function (Blueprint $table) {
            $table->foreign('docente_id')->references('id')->on('docentes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            try {
                $table->dropForeign('cursos_docente_id_foreign');
            } catch (\Throwable $e) {
                try { $table->dropForeign(['docente_id']); } catch (\Throwable $e2) {}
            }
        });

        Schema::table('cursos', function (Blueprint $table) {
            $table->foreign('docente_id')->references('id')->on('docentes');
        });
    }
};
