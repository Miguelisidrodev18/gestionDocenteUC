<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->string('numero')->nullable();
            $table->date('fecha');
            $table->string('hora_inicio')->nullable();
            $table->string('hora_fin')->nullable();
            $table->string('modalidad')->nullable();
            $table->string('responsable')->nullable();
            $table->json('asistentes')->nullable();
            $table->json('acuerdos')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actas');
    }
};

