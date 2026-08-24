<?php

use App\Livewire\Reports;
use App\Models\Reservation;
use App\Models\Vehicle;
use Illuminate\Http\Response;
use Livewire\Livewire;

it('shows approved reservations within the default range', function () {
    $user = createUserWithRole('consultas');
    $vehicle = Vehicle::factory()->create(['plate' => '7777GGG']);

    Reservation::factory()->approved()->create([
        'user_id' => $user->id,
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->subDays(5),
        'end_at' => now()->subDays(5)->addHours(3),
        'destination' => 'Tarija',
    ]);

    Livewire::actingAs($user)
        ->test(Reports::class)
        ->assertSee('7777GGG')
        ->assertSee('Tarija')
        ->assertViewHas('usageRows', fn ($rows) => $rows
            ->firstWhere(fn ($row) => $row['vehicle']->id === $vehicle->id)['occupations']->count() === 1);
});

it('does not count pending or rejected reservations', function () {
    $user = createUserWithRole('consultas');
    $vehicle = Vehicle::factory()->create();

    Reservation::factory()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->subDays(2),
        'end_at' => now()->subDays(2)->addHours(3),
    ]);

    Reservation::factory()->rejected()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->subDay(),
        'end_at' => now()->subDay()->addHours(3),
    ]);

    Livewire::actingAs($user)
        ->test(Reports::class)
        ->assertViewHas('usageRows', fn ($rows) => $rows
            ->firstWhere(fn ($row) => $row['vehicle']->id === $vehicle->id)['occupations']->isEmpty());
});

it('filters usage by vehicle', function () {
    $user = createUserWithRole('consultas');
    $vehicleA = Vehicle::factory()->create(['plate' => '8888HHH']);
    $vehicleB = Vehicle::factory()->create(['plate' => '9999JJJ']);

    Reservation::factory()->approved()->create([
        'vehicle_id' => $vehicleA->id,
        'start_at' => now()->subDays(2),
        'end_at' => now()->subDays(2)->addHours(2),
    ]);

    Livewire::actingAs($user)
        ->test(Reports::class)
        ->set('vehicleId', $vehicleA->id)
        ->assertViewHas('usageRows', function ($rows) use ($vehicleA, $vehicleB) {
            return $rows->count() === 1
                && $rows->first()['vehicle']->id === $vehicleA->id
                && $rows->first()['occupations']->count() === 1
                && $rows->doesntContain(fn ($row) => $row['vehicle']->id === $vehicleB->id);
        });
});

it('computes reserved hours for the range', function () {
    $user = createUserWithRole('consultas');
    $vehicle = Vehicle::factory()->create();

    Reservation::factory()->approved()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->subDays(2),
        'end_at' => now()->subDays(2)->addHours(2),
    ]);

    Livewire::actingAs($user)
        ->test(Reports::class)
        ->assertViewHas('usageRows', function ($rows) use ($vehicle) {
            $row = $rows->firstWhere(fn ($item) => $item['vehicle']->id === $vehicle->id);

            return $row !== null && abs($row['reservedSeconds'] - 7200) < 60;
        });
});

it('filters usage by requesting driver', function () {
    $viewer = createUserWithRole('consultas');
    $driverA = createUserWithRole('chofer');
    $driverB = createUserWithRole('chofer');
    $vehicle = Vehicle::factory()->create(['plate' => '1010KKK']);

    Reservation::factory()->approved()->create([
        'user_id' => $driverA->id,
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->subDays(3),
        'end_at' => now()->subDays(3)->addHours(4),
    ]);

    Livewire::actingAs($viewer)
        ->test(Reports::class)
        ->set('driverId', $driverB->id)
        ->assertViewHas('usageRows', function ($rows) use ($vehicle) {
            return $rows->firstWhere(fn ($row) => $row['vehicle']->id === $vehicle->id)['occupations']->isEmpty();
        })
        ->set('driverId', $driverA->id)
        ->assertViewHas('usageRows', function ($rows) use ($vehicle) {
            return $rows->firstWhere(fn ($row) => $row['vehicle']->id === $vehicle->id)['occupations']->count() === 1;
        });
});

it('lists active maintenances with their period', function () {
    $user = createUserWithRole('consultas');
    Vehicle::factory()->maintenance()->create([
        'plate' => '1212LLL',
        'maintenance_start_at' => now()->subDay(),
        'maintenance_end_at' => now()->addDays(4),
    ]);

    Livewire::actingAs($user)
        ->test(Reports::class)
        ->assertSee(__('Mantenimientos activos'))
        ->assertSee('1212LLL');
});

it('exports the report as pdf', function () {
    $user = createUserWithRole('consultas');
    $vehicle = Vehicle::factory()->create();
    Vehicle::factory()->maintenance()->create();

    Reservation::factory()->approved()->create([
        'user_id' => $user->id,
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->subDays(1),
        'end_at' => now()->subDays(1)->addHours(3),
    ]);

    $response = $this->actingAs($user)->get(route('reportes.pdf', [
        'start' => now()->subDays(30)->format('Y-m-d'),
        'end' => now()->format('Y-m-d'),
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('rejects an invalid range for the pdf export', function () {
    $user = createUserWithRole('consultas');

    $this->actingAs($user)
        ->get(route('reportes.pdf', ['start' => now()->format('Y-m-d'), 'end' => now()->subDays(5)->format('Y-m-d')]))
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
});
