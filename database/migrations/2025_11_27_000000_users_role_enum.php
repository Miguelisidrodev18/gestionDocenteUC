<?php

use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)
                    ->default(Role::DOCENTE->value);
            });

            return;
        }

        // Normalizar valores existentes a los del enum (por si hay mayúsculas u otros)
        DB::table('users')->select('id', 'role')->orderBy('id')->chunkById(200, function ($users) {
            foreach ($users as $user) {
                $value = is_string($user->role) ? strtolower($user->role) : null;
                $normalized = match ($value) {
                    Role::ADMIN->value => Role::ADMIN->value,
                    Role::RESPONSABLE->value => Role::RESPONSABLE->value,
                    default => Role::DOCENTE->value,
                };

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['role' => $normalized]);
            }
        });
    }

    public function down(): void
    {
        // No cambiamos el esquema en down para evitar pérdida de información.
        // Si se requiere, aquí se podría restaurar el tipo original.
    }
};

