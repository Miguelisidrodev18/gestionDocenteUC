<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modalidades', function (Blueprint $table) {
            if (! Schema::hasColumn('modalidades', 'bloque_id')) {
                $table->foreignId('bloque_id')
                    ->nullable()
                    ->after('semanas_por_bloque')
                    ->constrained('bloques')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('modalidades', function (Blueprint $table) {
            if (Schema::hasColumn('modalidades', 'bloque_id')) {
                $table->dropConstrainedForeignId('bloque_id');
            }
        });
    }
};
