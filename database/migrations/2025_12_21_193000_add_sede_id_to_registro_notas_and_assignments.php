<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_notas', function (Blueprint $table) {
            if (! Schema::hasColumn('registro_notas', 'sede_id')) {
                $table->foreignId('sede_id')
                    ->nullable()
                    ->after('campus_id')
                    ->constrained('sedes')
                    ->nullOnDelete()
                    ->restrictOnUpdate();
            }
        });

        Schema::table('assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('assignments', 'sede_id')) {
                $table->foreignId('sede_id')
                    ->nullable()
                    ->after('campus_id')
                    ->constrained('sedes')
                    ->nullOnDelete()
                    ->restrictOnUpdate();
            }
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            if (Schema::hasColumn('assignments', 'sede_id')) {
                $table->dropConstrainedForeignId('sede_id');
            }
        });

        Schema::table('registro_notas', function (Blueprint $table) {
            if (Schema::hasColumn('registro_notas', 'sede_id')) {
                $table->dropConstrainedForeignId('sede_id');
            }
        });
    }
};
