<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informe_finals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->string('responsable')->nullable();
            $table->date('fecha_presentacion')->nullable();
            $table->json('resultados')->nullable(); // resultados por sede y evaluación
            $table->json('mejoras')->nullable(); // oportunidades de mejora por sede
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informe_finals');
    }
};
