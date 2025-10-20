<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            // Use dateTime to avoid MySQL strict default issues with timestamp
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('location')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
