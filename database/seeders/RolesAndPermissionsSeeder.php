<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Crear permisos
        Permission::create(['name' => 'manage users']);
        Permission::create(['name' => 'view dashboard']);

        // Crear roles
        $admin = Role::create(['name' => 'admin']);
        $staff = Role::create(['name' => 'staff']);
        $client = Role::create(['name' => 'client']);

        // Asignar permisos a los roles
        $admin->givePermissionTo('manage users');
        $staff->givePermissionTo('view dashboard');
    }
}
