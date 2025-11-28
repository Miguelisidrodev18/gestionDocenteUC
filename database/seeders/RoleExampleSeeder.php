<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleExampleSeeder extends Seeder
{
    public function run(): void
    {
        // Admin de ejemplo
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin Demo',
                'password' => Hash::make('password'),
                'role' => Role::ADMIN,
            ],
        );

        // Responsable de ejemplo
        User::firstOrCreate(
            ['email' => 'responsable@example.com'],
            [
                'name' => 'Responsable Demo',
                'password' => Hash::make('password'),
                'role' => Role::RESPONSABLE,
            ],
        );

        // Docente de ejemplo
        User::firstOrCreate(
            ['email' => 'docente@example.com'],
            [
                'name' => 'Docente Demo',
                'password' => Hash::make('password'),
                'role' => Role::DOCENTE,
            ],
        );
    }
}

