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
        Schema::create('actualizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes');
            $table->string('titulo');
            $table->text('descripcion');
            $table->enum('tipo', ['capacitacion', 'certificacion', 'titulo', 'otro']);
            $table->date('fecha_obtencion');
            $table->string('institucion');
            $table->string('documento_url')->nullable(); // Para almacenar la URL del documento probatorio
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actualizaciones');
    }
};
