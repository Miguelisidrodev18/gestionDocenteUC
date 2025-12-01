<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('updates', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->longText('cuerpo_md');
            $table->enum('audience', ['TODOS', 'DOCENTES', 'RESPONSABLES', 'ADMIN'])->default('TODOS');
            $table->boolean('pinned')->default(false);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->foreignId('creado_por')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['audience', 'starts_at', 'ends_at']);
            $table->index(['pinned', 'starts_at'], 'updates_pinned_idx');
        });

        Schema::create('update_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('update_id')->constrained('updates')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('read_at');
            $table->timestamps();

            $table->unique(['update_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('update_reads');
        Schema::dropIfExists('updates');
    }
};

