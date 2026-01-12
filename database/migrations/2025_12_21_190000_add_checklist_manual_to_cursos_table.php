<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            if (! Schema::hasColumn('cursos', 'checklist_manual')) {
                $table->json('checklist_manual')->nullable()->after('review_state');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            if (Schema::hasColumn('cursos', 'checklist_manual')) {
                $table->dropColumn('checklist_manual');
            }
        });
    }
};
