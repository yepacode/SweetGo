<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Roles del sistema (propuesta: administrador y vendedor)
        $admin = Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'vendedor']);

        // Usuario administrador inicial
        $user = User::firstOrCreate(
            ['email' => 'admin@sweetgo.com'],
            [
                'name' => 'Administrador Sweet Go',
                'password' => Hash::make('password'),
            ]
        );

        $user->assignRole($admin);
    }
}
