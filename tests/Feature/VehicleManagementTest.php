<?php

use App\Enums\VehicleStatus;
use App\Livewire\VehicleManagement;
use App\Models\Reservation;
use App\Models\Vehicle;
use Livewire\Livewire;

it('creates a vehicle through the livewire component', function () {
    $user = createUserWithRole('responsable');

    Livewire::actingAs($user)
        ->test(VehicleManagement::class)
        ->call('create')
        ->set('plate', '9999ZZZ')
        ->set('brand', 'Toyota')
        ->set('model', 'Land Cruiser')
        ->set('year', 2023)
        ->set('capacity', 8)
        ->call('save')
        ->assertHasNoErrors();

    expect(Vehicle::where('plate', '9999ZZZ')->exists())->toBeTrue();
});

it('prevents duplicate plates', function () {
    $user = createUserWithRole('responsable');
    Vehicle::factory()->create(['plate' => '9999ZZZ']);

    Livewire::actingAs($user)
        ->test(VehicleManagement::class)
        ->call('create')
        ->set('plate', '9999ZZZ')
        ->set('brand', 'Toyota')
        ->set('model', 'Corolla')
        ->set('capacity', 4)
        ->call('save')
        ->assertHasErrors(['plate']);
});

it('allows updating a vehicle keeping its own plate', function () {
    $user = createUserWithRole('responsable');
    $vehicle = Vehicle::factory()->create(['plate' => '7777AAA']);

    Livewire::actingAs($user)
        ->test(VehicleManagement::class)
        ->call('edit', $vehicle->id)
        ->assertSet('plate', '7777AAA')
        ->set('brand', 'Honda')
        ->call('save')
        ->assertHasNoErrors();

    expect($vehicle->fresh()->brand)->toBe('Honda');
});

it('blocks vehicle creation for users without permissions', function () {
    $user = createUserWithRole('chofer');

    Livewire::actingAs($user)
        ->test(VehicleManagement::class)
        ->call('create')
        ->assertForbidden();
});

it('marks a vehicle as maintenance', function () {
    $user = createUserWithRole('responsable');
    $vehicle = Vehicle::factory()->create();

    Livewire::actingAs($user)
        ->test(VehicleManagement::class)
        ->call('edit', $vehicle->id)
        ->set('status', VehicleStatus::Mantenimiento->value)
        ->call('save')
        ->assertHasErrors(['maintenanceStartAt', 'maintenanceEndAt'])
        ->set('maintenanceStartAt', now()->format('Y-m-d\TH:i'))
        ->set('maintenanceEndAt', now()->addDays(3)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    expect($vehicle->fresh()->status)->toBe(VehicleStatus::Mantenimiento);
});

it('stores the maintenance period when marking a vehicle as maintenance', function () {
    $user = createUserWithRole('responsable');
    $vehicle = Vehicle::factory()->create();

    Livewire::actingAs($user)
        ->test(VehicleManagement::class)
        ->call('edit', $vehicle->id)
        ->set('status', VehicleStatus::Mantenimiento->value)
        ->set('maintenanceStartAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('maintenanceEndAt', now()->addDays(5)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    expect($vehicle->fresh()->status)->toBe(VehicleStatus::Mantenimiento)
        ->and($vehicle->fresh()->maintenance_start_at)->not->toBeNull()
        ->and($vehicle->fresh()->maintenance_end_at)->not->toBeNull();
});

it('rejects a maintenance end before its start', function () {
    $user = createUserWithRole('responsable');
    $vehicle = Vehicle::factory()->create();

    Livewire::actingAs($user)
        ->test(VehicleManagement::class)
        ->call('edit', $vehicle->id)
        ->set('status', VehicleStatus::Mantenimiento->value)
        ->set('maintenanceStartAt', now()->addDays(5)->format('Y-m-d\TH:i'))
        ->set('maintenanceEndAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['maintenanceEndAt']);
});

it('clears the maintenance period when the vehicle becomes available again', function () {
    $user = createUserWithRole('responsable');
    $vehicle = Vehicle::factory()->maintenance()->create([
        'maintenance_start_at' => now(),
        'maintenance_end_at' => now()->addDays(2),
    ]);

    Livewire::actingAs($user)
        ->test(VehicleManagement::class)
        ->call('edit', $vehicle->id)
        ->set('status', VehicleStatus::Disponible->value)
        ->call('save')
        ->assertHasNoErrors();

    expect($vehicle->fresh()->status)->toBe(VehicleStatus::Disponible)
        ->and($vehicle->fresh()->maintenance_start_at)->toBeNull()
        ->and($vehicle->fresh()->maintenance_end_at)->toBeNull();
});

it('deletes a vehicle that has no reservations', function () {
    $user = createUserWithRole('responsable');
    $vehicle = Vehicle::factory()->create();

    Livewire::actingAs($user)
        ->test(VehicleManagement::class)
        ->call('delete', $vehicle->id);

    expect(Vehicle::find($vehicle->id))->toBeNull();
});

it('does not delete a vehicle with reservations', function () {
    $user = createUserWithRole('responsable');
    $vehicle = Vehicle::factory()->create();
    Reservation::factory()->create(['vehicle_id' => $vehicle->id]);

    Livewire::actingAs($user)
        ->test(VehicleManagement::class)
        ->call('delete', $vehicle->id);

    expect(Vehicle::find($vehicle->id))->not->toBeNull();
});
