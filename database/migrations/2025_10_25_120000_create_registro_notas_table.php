<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->foreignId('docente_id')->nullable()->constrained('docentes')->nullOnDelete();
            $table->string('docente_nombre')->nullable();
            $table->string('campus')->nullable();
            $table->string('nrc')->nullable();
            $table->unsignedInteger('total_estudiantes')->default(0);
            $table->unsignedInteger('c1_aprobados')->default(0);
            $table->unsignedInteger('c1_desaprobados')->default(0);
            $table->float('c1_promedio')->default(0);
            $table->unsignedInteger('ep_aprobados')->default(0);
            $table->unsignedInteger('ep_desaprobados')->default(0);
            $table->float('ep_promedio')->default(0);
            $table->text('hipotesis_c1')->nullable();
            $table->text('hipotesis_ep')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_notas');
    }
};

