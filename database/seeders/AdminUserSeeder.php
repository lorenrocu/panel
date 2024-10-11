<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // Asumiendo que tienes un modelo User
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Crea el usuario administrador
        $user = User::create([
            'name' => 'Lorenzo',
            'email' => 'admin@admin.com',
            'password' => Hash::make('123456'), // Puedes cambiar la contraseña
        ]);

        // Asignar el rol de administrador
        $user->assignRole('admin'); // Esto depende de que uses un paquete como Spatie Role & Permission
    }
}
