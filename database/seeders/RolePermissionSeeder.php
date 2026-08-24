<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public const ROLES = ['administrador', 'responsable', 'chofer', 'consultas'];

    public const PERMISSIONS = [
        'ver vehiculos',
        'crear vehiculos',
        'editar vehiculos',
        'eliminar vehiculos',
        'ver reservas',
        'crear reservas',
        'aprobar reservas',
        'ver reportes',
        'gestionar usuarios',
        'gestionar roles',
    ];

    /**
     * Seed the application's roles and permissions.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $byRole = [
            'administrador' => self::PERMISSIONS,
            'responsable' => [
                'ver vehiculos',
                'crear vehiculos',
                'editar vehiculos',
                'eliminar vehiculos',
                'ver reservas',
                'crear reservas',
                'aprobar reservas',
                'ver reportes',
                'gestionar usuarios',
            ],
            'chofer' => [
                'ver vehiculos',
                'ver reservas',
                'crear reservas',
            ],
            'consultas' => [
                'ver vehiculos',
                'ver reservas',
                'ver reportes',
            ],
        ];

        foreach ($byRole as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($permissions);
        }
    }
}
