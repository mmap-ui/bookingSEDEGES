<?php

use App\Livewire\RoleManagement;
use App\Livewire\UserManagement;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('creates a user with a role and a confirmation password', function () {
    $admin = createUserWithRole('administrador');

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('create')
        ->set('name', 'Nuevo Usuario')
        ->set('email', 'nuevo@example.com')
        ->set('password', 'secret123')
        ->set('password_confirmation', 'secret123')
        ->set('role', 'chofer')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $user = User::where('email', 'nuevo@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole('chofer'))->toBeTrue();
});

it('requires the password confirmation to match when creating a user', function () {
    $admin = createUserWithRole('administrador');

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('create')
        ->set('name', 'Otro Usuario')
        ->set('email', 'otro@example.com')
        ->set('password', 'secret123')
        ->set('password_confirmation', 'different')
        ->set('role', 'chofer')
        ->call('save')
        ->assertHasErrors(['password']);

    expect(User::where('email', 'otro@example.com')->exists())->toBeFalse();
});

it('lets a responsable create a non-admin user', function () {
    $admin = createUserWithRole('responsable');

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('create')
        ->set('name', 'Chofer Nuevo')
        ->set('email', 'chofer@example.com')
        ->set('password', 'secret123')
        ->set('password_confirmation', 'secret123')
        ->set('role', 'chofer')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('email', 'chofer@example.com')->exists())->toBeTrue();
});

it('does not let a responsable assign the admin role', function () {
    $admin = createUserWithRole('responsable');

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('create')
        ->set('name', 'Falso Admin')
        ->set('email', 'falso@example.com')
        ->set('password', 'secret123')
        ->set('password_confirmation', 'secret123')
        ->set('role', 'administrador')
        ->call('save')
        ->assertHasErrors(['role']);

    expect(User::where('email', 'falso@example.com')->exists())->toBeFalse();
});

it('updates a user without requiring a password change', function () {
    $admin = createUserWithRole('administrador');
    $user = createUserWithRole('chofer');

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('edit', $user->id)
        ->assertSet('password_confirmation', null)
        ->set('name', 'Nombre Editado')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Nombre Editado');
});

it('blocks user creation for users without permissions', function () {
    $user = createUserWithRole('consultas');

    Livewire::actingAs($user)
        ->test(UserManagement::class)
        ->call('create')
        ->assertForbidden();
});

it('creates a role with permissions', function () {
    $admin = createUserWithRole('administrador');
    $permission = Permission::where('name', 'ver reservas')->first();

    Livewire::actingAs($admin)
        ->test(RoleManagement::class)
        ->call('create')
        ->set('name', 'auditor')
        ->set('permissions', [$permission->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    expect(Role::findByName('auditor')->hasPermissionTo('ver reservas'))->toBeTrue();
});

it('edits a role keeping its permissions', function () {
    $admin = createUserWithRole('administrador');
    $role = Role::create(['name' => 'secretario']);
    $permission = Permission::where('name', 'ver reportes')->first();

    Livewire::actingAs($admin)
        ->test(RoleManagement::class)
        ->call('edit', $role->id)
        ->set('permissions', [$permission->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($role->fresh()->hasPermissionTo('ver reportes'))->toBeTrue();
});

it('does not let a chofer manage roles', function () {
    $user = createUserWithRole('chofer');

    Livewire::actingAs($user)
        ->test(RoleManagement::class)
        ->call('create')
        ->assertForbidden();
});

it('does not delete the administrator role', function () {
    $admin = createUserWithRole('administrador');
    $role = Role::findByName('administrador');

    Livewire::actingAs($admin)
        ->test(RoleManagement::class)
        ->call('delete', $role->id);

    expect(Role::findByName('administrador'))->not->toBeNull();
});

it('does not delete a role with assigned users', function () {
    $admin = createUserWithRole('administrador');
    $user = createUserWithRole('chofer');
    $role = Role::findByName('chofer');

    Livewire::actingAs($admin)
        ->test(RoleManagement::class)
        ->call('delete', $role->id);

    expect(Role::findByName('chofer'))->not->toBeNull()
        ->and($user->hasRole('chofer'))->toBeTrue();
});
