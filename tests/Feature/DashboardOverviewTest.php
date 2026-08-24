<?php

use App\Enums\ReservationStatus;
use App\Livewire\DashboardOverview;
use App\Models\Reservation;
use App\Models\Vehicle;
use Livewire\Livewire;

it('shows the pending requests count badge in the navigation', function () {
    $approver = createUserWithRole('responsable');
    Reservation::factory()->count(2)->create();

    $this->actingAs($approver)
        ->get(route('solicitudes'))
        ->assertOk()
        ->assertSee('(2)');
});

it('does not show the pending badge when there are no pending requests', function () {
    $approver = createUserWithRole('responsable');

    $this->actingAs($approver)
        ->get(route('solicitudes'))
        ->assertOk()
        ->assertDontSee('(0)');
});

it('lists scheduled and free vehicles for today', function () {
    $user = createUserWithRole('chofer');
    $scheduledVehicle = Vehicle::factory()->create(['plate' => '1111AAA']);
    $freeVehicle = Vehicle::factory()->create(['plate' => '2222BBB']);

    Reservation::factory()->approved()->create([
        'vehicle_id' => $scheduledVehicle->id,
        'start_at' => today()->setTime(8, 0),
        'end_at' => today()->setTime(12, 0),
    ]);

    Livewire::actingAs($user)
        ->test(DashboardOverview::class)
        ->assertSee('1111AAA')
        ->assertSee('2222BBB')
        ->assertViewHas('scheduledToday', fn ($scheduled) => $scheduled->contains('vehicle_id', $scheduledVehicle->id))
        ->assertViewHas('freeVehicles', fn ($free) => $free->contains('id', $freeVehicle->id));
});

it('does not list a vehicle as free when it has a reservation overlapping today', function () {
    $user = createUserWithRole('chofer');
    $vehicle = Vehicle::factory()->create(['plate' => '4444DDD']);

    Reservation::factory()->approved()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => today()->subDay()->setTime(20, 0),
        'end_at' => today()->setTime(10, 0),
    ]);

    Livewire::actingAs($user)
        ->test(DashboardOverview::class)
        ->assertViewHas('freeVehicles', fn ($free) => $free->doesntContain('id', $vehicle->id));
});

it('shows maintenance vehicles separately', function () {
    $user = createUserWithRole('consultas');
    Vehicle::factory()->maintenance()->create(['plate' => '3333CCC']);

    Livewire::actingAs($user)
        ->test(DashboardOverview::class)
        ->assertViewHas('maintenanceVehicles', fn ($maintenance) => $maintenance->contains('plate', '3333CCC'));
});

it('shows driver request stats for chofer users', function () {
    $driver = createUserWithRole('chofer');

    Reservation::factory()->count(2)->create(['user_id' => $driver->id]);
    Reservation::factory()->approved($driver)->create(['user_id' => $driver->id]);
    Reservation::factory()->rejected()->create(['user_id' => $driver->id]);

    Livewire::actingAs($driver)
        ->test(DashboardOverview::class)
        ->assertViewHas('driverStats', fn (array $stats) => $stats[ReservationStatus::Pendiente->value] === 2
            && $stats[ReservationStatus::Aprobada->value] === 1
            && $stats[ReservationStatus::Rechazada->value] === 1);
});

it('shows admin stats and vehicle usage frequency for approvers', function () {
    $admin = createUserWithRole('administrador');
    $vehicle = Vehicle::factory()->create();

    Reservation::factory()->count(3)->approved()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->subDays(2),
    ]);
    Reservation::factory()->rejected()->create(['vehicle_id' => $vehicle->id]);

    Livewire::actingAs($admin)
        ->test(DashboardOverview::class)
        ->assertViewHas('adminStats', fn (array $stats) => $stats[ReservationStatus::Rechazada->value] === 1
            && $stats[ReservationStatus::Aprobada->value] === 3)
        ->assertViewHas('vehicleUsage', function ($usage) use ($vehicle) {
            $row = $usage->firstWhere('id', $vehicle->id);

            return $row !== null && $row->approved_reservations_30d === 3;
        });
});

it('hides role specific sections for consultas users', function () {
    $user = createUserWithRole('consultas');

    Livewire::actingAs($user)
        ->test(DashboardOverview::class)
        ->assertViewHas('driverStats', null)
        ->assertViewHas('adminStats', null);
});

it('shows reservations currently in progress with return info', function () {
    $driver = createUserWithRole('chofer');
    $vehicle = Vehicle::factory()->create(['plate' => '5555EEE']);

    Reservation::factory()->approved()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->subHours(2),
        'end_at' => now()->addHours(2),
        'destination' => 'Sucre',
    ]);

    Livewire::actingAs($driver)
        ->test(DashboardOverview::class)
        ->assertSee(__('En curso ahora'))
        ->assertSee('5555EEE')
        ->assertSee('Sucre')
        ->assertViewHas('inProgressNow', fn ($inProgress) => $inProgress->count() === 1);
});

it('shows upcoming departures for the next seven days', function () {
    $user = createUserWithRole('consultas');
    $vehicle = Vehicle::factory()->create();

    Reservation::factory()->approved()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDays(2),
        'end_at' => now()->addDays(2)->addHours(3),
    ]);

    Reservation::factory()->approved()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDays(10),
        'end_at' => now()->addDays(10)->addHours(3),
    ]);

    Livewire::actingAs($user)
        ->test(DashboardOverview::class)
        ->assertViewHas('upcomingThisWeek', fn ($upcoming) => $upcoming->count() === 1);
});

it('alerts approvers about pending requests', function () {
    $approver = createUserWithRole('responsable');
    Reservation::factory()->count(2)->create();

    Livewire::actingAs($approver)
        ->test(DashboardOverview::class)
        ->assertSee(__('Ir a solicitudes'))
        ->assertViewHas('pendingApprovalsCount', 2);
});

it('does not alert non approvers about pending requests', function () {
    $driver = createUserWithRole('chofer');
    Reservation::factory()->count(2)->create();

    Livewire::actingAs($driver)
        ->test(DashboardOverview::class)
        ->assertViewHas('pendingApprovalsCount', 0);
});

it('suggests a review for heavily used vehicles', function () {
    $admin = createUserWithRole('administrador');
    $vehicle = Vehicle::factory()->create(['plate' => '6666FFF']);

    Reservation::factory()->count(8)->approved()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->subDays(2),
    ]);

    Livewire::actingAs($admin)
        ->test(DashboardOverview::class)
        ->assertViewHas('highUsageThreshold', 8)
        ->assertSee(__('Sugerir revisión'));
});
