<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('status', 20)
                ->default('aceptado')
                ->after('email_sent_at');
            $table->foreignId('invited_by')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('responded_at')
                ->nullable()
                ->after('invited_by');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropForeign(['invited_by']);
            $table->dropColumn(['status', 'invited_by', 'responded_at']);
        });
    }
};

