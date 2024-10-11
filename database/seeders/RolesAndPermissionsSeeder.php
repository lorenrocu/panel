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
        Permission::create(['name' => 'edit content']);  // Ejemplo de otro permiso que puedes añadir

        // Crear roles
        $admin = Role::create(['name' => 'admin']);
        $staff = Role::create(['name' => 'staff']);
        $client = Role::create(['name' => 'client']);

        // Asignar todos los permisos al rol de admin
        $admin->givePermissionTo(Permission::all());

        // Asignar permisos específicos a otros roles
        $staff->givePermissionTo('view dashboard');
    }
}
