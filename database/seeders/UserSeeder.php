<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed users for each role with a predictable password.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Administrador', 'email' => 'admin@sedeges.test', 'role' => 'administrador'],
            ['name' => 'Responsable de Flota', 'email' => 'responsable@sedeges.test', 'role' => 'responsable'],
            ['name' => 'Chofer', 'email' => 'chofer@sedeges.test', 'role' => 'chofer'],
            ['name' => 'Consultas', 'email' => 'consultas@sedeges.test', 'role' => 'consultas'],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );

            $user->syncRoles([$data['role']]);
        }
    }
}
