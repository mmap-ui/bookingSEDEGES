<?php

use App\Livewire\Calendar;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Livewire\Livewire;

it('returns calendar events for approved and pending reservations', function () {
    $user = createUserWithRole('consultas');
    $vehicle = Vehicle::factory()->create();

    $approved = Reservation::factory()->approved()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(9, 0),
        'end_at' => now()->addDay()->setTime(12, 0),
    ]);

    $pending = Reservation::factory()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(15, 0),
        'end_at' => now()->addDay()->setTime(18, 0),
    ]);

    $response = $this->actingAs($user)->getJson(route('calendario.eventos'))
        ->assertOk();

    $response->assertJsonCount(2);

    collect($response->json())->each(function (array $event) {
        expect($event)->toHaveKeys(['id', 'title', 'start', 'end', 'color', 'extendedProps']);
    });

    $colors = collect($response->json())->pluck('color');
    expect($colors)->toContain('#059669')->toContain('#d97706');
});

it('filters events by vehicle', function () {
    $user = createUserWithRole('consultas');
    $vehicleA = Vehicle::factory()->create();
    $vehicleB = Vehicle::factory()->create();

    Reservation::factory()->approved()->create(['vehicle_id' => $vehicleA->id]);
    Reservation::factory()->approved()->create(['vehicle_id' => $vehicleB->id]);

    $this->actingAs($user)
        ->getJson(route('calendario.eventos', ['vehicle' => $vehicleA->id]))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.extendedProps.vehicleId', $vehicleA->id);
});

it('filters events by status', function () {
    $user = createUserWithRole('consultas');
    $vehicle = Vehicle::factory()->create();

    Reservation::factory()->approved()->create(['vehicle_id' => $vehicle->id]);
    Reservation::factory()->create(['vehicle_id' => $vehicle->id]);

    $this->actingAs($user)
        ->getJson(route('calendario.eventos', ['status' => 'aprobada']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.extendedProps.status', 'aprobada');
});

it('dispatches a refresh event when the vehicle filter changes', function () {
    $user = createUserWithRole('consultas');

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->set('vehicleId', 1)
        ->assertDispatched('calendar-filters-updated');
});

it('dispatches a refresh event when the status filter changes', function () {
    $user = createUserWithRole('consultas');

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->set('status', 'aprobada')
        ->assertDispatched('calendar-filters-updated');
});

it('does not expose rejected or cancelled reservations in the calendar', function () {
    $user = createUserWithRole('consultas');
    $vehicle = Vehicle::factory()->create();

    Reservation::factory()->rejected()->create(['vehicle_id' => $vehicle->id]);
    Reservation::factory()->cancelled()->create(['vehicle_id' => $vehicle->id]);

    $this->actingAs($user)
        ->getJson(route('calendario.eventos'))
        ->assertOk()
        ->assertJsonCount(0);
});

it('includes overlapping pending conflicts in event details', function () {
    $user = createUserWithRole('consultas');
    $vehicle = Vehicle::factory()->create();

    $first = Reservation::factory()->create([
        'user_id' => $user->id,
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(9, 0),
        'end_at' => now()->addDay()->setTime(12, 0),
    ]);

    $second = Reservation::factory()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(10, 0),
        'end_at' => now()->addDay()->setTime(13, 0),
    ]);

    $events = collect($this->actingAs($user)->getJson(route('calendario.eventos'))->json());

    $firstEvent = $events->firstWhere('extendedProps.id', $first->id);

    expect($firstEvent['extendedProps']['conflicts'])->toHaveCount(1)
        ->and($firstEvent['extendedProps']['conflicts'][0]['requester'])->toBe($second->user->name);
});

it('does not report conflicts for approved reservations', function () {
    $user = createUserWithRole('consultas');
    $vehicle = Vehicle::factory()->create();

    $approved = Reservation::factory()->approved()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(9, 0),
        'end_at' => now()->addDay()->setTime(12, 0),
    ]);

    Reservation::factory()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(10, 0),
        'end_at' => now()->addDay()->setTime(13, 0),
    ]);

    $events = collect($this->actingAs($user)->getJson(route('calendario.eventos'))->json());

    $approvedEvent = $events->firstWhere('extendedProps.id', $approved->id);

    expect($approvedEvent['extendedProps']['conflicts'])->toBe([]);
});

it('requires authentication to view events', function () {
    $this->getJson(route('calendario.eventos'))->assertUnauthorized();
});

it('requires the view permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('calendario'))->assertForbidden();
});
