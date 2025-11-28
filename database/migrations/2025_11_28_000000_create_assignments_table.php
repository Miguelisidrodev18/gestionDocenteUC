<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->foreignId('responsable_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->string('modalidad_docente', 100)->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamps();
            $table->unique('curso_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};

