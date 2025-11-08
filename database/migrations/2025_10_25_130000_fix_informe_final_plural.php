<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasSingular = Schema::hasTable('informe_final');
        $hasPlural = Schema::hasTable('informe_finals');

        if ($hasSingular && ! $hasPlural) {
            Schema::rename('informe_final', 'informe_finals');
        } elseif (! $hasPlural) {
            Schema::create('informe_finals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
                $table->string('responsable')->nullable();
                $table->date('fecha_presentacion')->nullable();
                $table->json('resultados')->nullable();
                $table->json('mejoras')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('informe_finals') && ! Schema::hasTable('informe_final')) {
            Schema::rename('informe_finals', 'informe_final');
        }
    }
};

