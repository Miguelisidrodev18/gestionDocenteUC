<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modalidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedTinyInteger('duracion_semanas');
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modalidades');
    }
};

