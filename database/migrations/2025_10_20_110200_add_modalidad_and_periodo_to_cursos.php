<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->foreignId('modalidad_id')->nullable()->after('modalidad')->constrained('modalidades');
            $table->string('periodo_academico')->nullable()->after('periodo');
        });
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('modalidad_id');
            $table->dropColumn('periodo_academico');
        });
    }
};

