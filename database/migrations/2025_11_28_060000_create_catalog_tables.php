<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });


        Schema::create('periodos_academicos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->date('inicio')->nullable();
            $table->date('fin')->nullable();
            $table->enum('estado', ['ABIERTO', 'CERRADO'])->default('ABIERTO');
            $table->timestamps();
        });

        Schema::create('tipos_evidencia', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->boolean('cuenta_en_avance')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bloques', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->unsignedTinyInteger('semanas')->default(8);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('requisitos_modalidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modalidad_id')
                ->constrained('modalidades')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('tipo_evidencia_id')
                ->constrained('tipos_evidencia')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('bloque_id')
                ->nullable()
                ->constrained('bloques')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->unsignedInteger('minimo')->default(0);
            $table->unsignedInteger('peso')->default(0);
            $table->timestamps();
        });

        // Extender Áreas
        Schema::table('areas', function (Blueprint $table) {
            if (! Schema::hasColumn('areas', 'codigo')) {
                $table->string('codigo')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('areas', 'activo')) {
                $table->boolean('activo')->default(true)->after('nombre');
            }
            if (! Schema::hasColumn('areas', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Extender Modalidades
        Schema::table('modalidades', function (Blueprint $table) {
            if (! Schema::hasColumn('modalidades', 'codigo')) {
                $table->string('codigo')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('modalidades', 'activo')) {
                $table->boolean('activo')->default(true)->after('duracion_semanas');
            }
            if (! Schema::hasColumn('modalidades', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Extender Cursos con FKs de catálogos
        Schema::table('cursos', function (Blueprint $table) {
            if (! Schema::hasColumn('cursos', 'sede_id')) {
                $table->foreignId('sede_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('sedes')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
            }
            if (! Schema::hasColumn('cursos', 'area_id')) {
                $table->foreignId('area_id')
                    ->nullable()
                    ->after('sede_id')
                    ->constrained('areas')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
            }
            if (! Schema::hasColumn('cursos', 'periodo_id')) {
                $table->foreignId('periodo_id')
                    ->nullable()
                    ->after('periodo')
                    ->constrained('periodos_academicos')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
            }
        });
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            if (Schema::hasColumn('cursos', 'periodo_id')) {
                $table->dropConstrainedForeignId('periodo_id');
            }
            if (Schema::hasColumn('cursos', 'area_id')) {
                $table->dropConstrainedForeignId('area_id');
            }
            if (Schema::hasColumn('cursos', 'sede_id')) {
                $table->dropConstrainedForeignId('sede_id');
            }
        });

        Schema::table('modalidades', function (Blueprint $table) {
            if (Schema::hasColumn('modalidades', 'codigo')) {
                $table->dropColumn('codigo');
            }
            if (Schema::hasColumn('modalidades', 'activo')) {
                $table->dropColumn('activo');
            }
            if (Schema::hasColumn('modalidades', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('areas', function (Blueprint $table) {
            if (Schema::hasColumn('areas', 'codigo')) {
                $table->dropColumn('codigo');
            }
            if (Schema::hasColumn('areas', 'activo')) {
                $table->dropColumn('activo');
            }
            if (Schema::hasColumn('areas', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::dropIfExists('requisitos_modalidad');
        Schema::dropIfExists('bloques');
        Schema::dropIfExists('tipos_evidencia');
        Schema::dropIfExists('periodos_academicos');
        Schema::dropIfExists('sedes');
    }
};
