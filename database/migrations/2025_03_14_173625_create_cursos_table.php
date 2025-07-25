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
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre',150);
            $table->string('codigo')->unique();
            $table->text('descripcion')->nullable();
            $table->integer('creditos');
            $table->enum('nivel',['pregrado','postgrado']);
            $table->string('modalidad')->default('presencial');
            $table->string('image_url')->nullable();
            $table->foreignId('docente_id')->constrained('docentes');
            $table->foreignId('user_id')->constrained('users'); // Agrega esta línea
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};
