<?php

use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('seeds all expected roles and permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Role::count())->toBe(4)
        ->and(Permission::count())->toBe(10);
});

it('gives each role the expected permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Role::findByName('administrador')->hasPermissionTo('eliminar vehiculos'))->toBeTrue()
        ->and(Role::findByName('administrador')->hasPermissionTo('gestionar roles'))->toBeTrue()
        ->and(Role::findByName('responsable')->hasPermissionTo('aprobar reservas'))->toBeTrue()
        ->and(Role::findByName('responsable')->hasPermissionTo('gestionar usuarios'))->toBeTrue()
        ->and(Role::findByName('responsable')->hasPermissionTo('gestionar roles'))->toBeFalse()
        ->and(Role::findByName('chofer')->hasPermissionTo('crear reservas'))->toBeTrue()
        ->and(Role::findByName('chofer')->hasPermissionTo('aprobar reservas'))->toBeFalse()
        ->and(Role::findByName('consultas')->hasPermissionTo('ver reportes'))->toBeTrue()
        ->and(Role::findByName('consultas')->hasPermissionTo('aprobar reservas'))->toBeFalse();
});

it('lets a responsable manage vehicles', function () {
    $user = createUserWithRole('responsable');

    expect($user->can('crear vehiculos'))->toBeTrue()
        ->and($user->can('editar vehiculos'))->toBeTrue()
        ->and($user->can('eliminar vehiculos'))->toBeTrue();
});

it('prevents a chofer from approving requests', function () {
    $user = createUserWithRole('chofer');

    expect($user->can('crear reservas'))->toBeTrue()
        ->and($user->can('aprobar reservas'))->toBeFalse();
});

it('blocks page access for users without the manage users permission', function () {
    $user = createUserWithRole('chofer');

    $this->actingAs($user)->get(route('usuarios'))->assertForbidden();
});
