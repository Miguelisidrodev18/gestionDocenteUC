<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_notas', function (Blueprint $table) {
            if (Schema::hasColumn('registro_notas', 'campus_id')) {
                $hasIndex = DB::selectOne(
                    "SELECT COUNT(1) AS count FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'registro_notas' AND index_name = 'registro_notas_curso_id_index'"
                );
                if (! $hasIndex || (int) $hasIndex->count === 0) {
                    $table->index('curso_id', 'registro_notas_curso_id_index');
                }

                $hasUnique = DB::selectOne(
                    "SELECT COUNT(1) AS count FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'registro_notas' AND index_name = 'registro_notas_unique_curso_campus_nrc_corte'"
                );
                if ($hasUnique && (int) $hasUnique->count > 0) {
                    $table->dropUnique('registro_notas_unique_curso_campus_nrc_corte');
                }
                $table->dropColumn('campus_id');
            }
            if (Schema::hasColumn('registro_notas', 'sede_id')) {
                $table->unique(['curso_id', 'sede_id', 'nrc', 'corte'], 'registro_notas_unique_curso_sede_nrc_corte');
            }
        });

        Schema::table('assignments', function (Blueprint $table) {
            if (Schema::hasColumn('assignments', 'campus_id')) {
                $table->dropColumn('campus_id');
            }
        });

        Schema::table('cursos', function (Blueprint $table) {
            if (Schema::hasColumn('cursos', 'campus_id')) {
                $table->dropConstrainedForeignId('campus_id');
            }
        });

        Schema::dropIfExists('campus');
    }

    public function down(): void
    {
        Schema::create('campus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')
                ->constrained('sedes')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('cursos', function (Blueprint $table) {
            if (! Schema::hasColumn('cursos', 'campus_id')) {
                $table->foreignId('campus_id')
                    ->nullable()
                    ->after('sede_id')
                    ->constrained('campus')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
            }
        });

        Schema::table('assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('assignments', 'campus_id')) {
                $table->unsignedBigInteger('campus_id')->nullable()->after('curso_id');
            }
        });

        Schema::table('registro_notas', function (Blueprint $table) {
            if (! Schema::hasColumn('registro_notas', 'campus_id')) {
                $table->unsignedBigInteger('campus_id')->nullable()->after('campus');
                $table->unique(['curso_id', 'campus_id', 'nrc', 'corte'], 'registro_notas_unique_curso_campus_nrc_corte');
            }
            if (Schema::hasColumn('registro_notas', 'sede_id')) {
                $table->dropUnique('registro_notas_unique_curso_sede_nrc_corte');
            }
        });
    }
};
