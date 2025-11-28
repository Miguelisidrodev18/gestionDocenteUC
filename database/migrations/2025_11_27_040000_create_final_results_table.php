<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->string('sede', 150);
            $table->unsignedInteger('aprobados')->default(0);
            $table->unsignedInteger('desaprobados')->default(0);
            $table->float('promedio')->default(0);
            $table->float('avance_promedio')->default(0); // porcentaje de aprobados
            $table->string('periodo', 20);
            $table->timestamps();
            $table->unique(['curso_id', 'sede']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_results');
    }
};

