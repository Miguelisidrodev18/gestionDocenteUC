<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('materiales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('curso_id'); // Clave foránea
            $table->string('titulo'); // Nombre del material
            $table->string('tipo')->nullable(); // Tipo de material (opcional)
            $table->string('ruta_archivo'); // Ruta o URL del archivo
            $table->timestamps();
            
            $table->foreign('curso_id')->references('id')->on('cursos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materiales');
    }
};
