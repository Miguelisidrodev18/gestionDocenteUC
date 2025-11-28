<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('advisor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->unsignedTinyInteger('current_load')->default(0);
            $table->unsignedTinyInteger('max_load')->default(4);
            $table->string('main_area')->nullable();
            $table->text('expertise')->nullable();
            $table->text('experience')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('advisor_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->foreignId('curso_id')->nullable()->constrained('cursos')->nullOnDelete();
            $table->string('type')->nullable(); // ej: tesis, curso, personal
            $table->text('description');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('panels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->string('type')->default('asesoria'); // asesoría / jurado / sustentación
            $table->dateTime('scheduled_at')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', ['borrador', 'pendiente', 'confirmado', 'cerrado'])->default('borrador');
            $table->unsignedTinyInteger('max_members')->default(3);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('advisor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_id')->constrained('panels')->cascadeOnDelete();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->string('role')->default('asesor'); // asesor / jurado / presidente
            $table->enum('status', ['invitado', 'aceptado', 'rechazado'])->default('invitado');
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['panel_id', 'docente_id']);
        });

        Schema::create('panel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_id')->constrained('panels')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panel_logs');
        Schema::dropIfExists('advisor_assignments');
        Schema::dropIfExists('panels');
        Schema::dropIfExists('advisor_conflicts');
        Schema::dropIfExists('advisor_profiles');
    }
};

