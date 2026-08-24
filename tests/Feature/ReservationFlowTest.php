<?php

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Services\ReservationService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('creates a pending reservation', function () {
    $user = createUserWithRole('chofer');
    $vehicle = Vehicle::factory()->create();

    $reservation = app(ReservationService::class)->create($user, [
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(9, 0),
        'end_at' => now()->addDay()->setTime(12, 0),
        'motive' => 'Traslado institucional',
        'destination' => 'Cochabamba',
    ]);

    expect($reservation->status)->toBe(ReservationStatus::Pendiente)
        ->and($reservation->user_id)->toBe($user->id)
        ->and($reservation->vehicle_id)->toBe($vehicle->id);
});

it('rejects a reservation that overlaps an approved one', function () {
    $user = createUserWithRole('chofer');
    $vehicle = Vehicle::factory()->create();

    $approved = Reservation::factory()->approved()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(9, 0),
        'end_at' => now()->addDay()->setTime(12, 0),
    ]);

    expect(fn () => app(ReservationService::class)->create($user, [
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(11, 0),
        'end_at' => now()->addDay()->setTime(13, 0),
        'motive' => 'Reunión',
        'destination' => 'La Paz',
    ]))->toThrow(ValidationException::class);

    expect(Reservation::count())->toBe(1);
});

it('allows a pending request even when another pending request overlaps', function () {
    $user = createUserWithRole('chofer');
    $vehicle = Vehicle::factory()->create();

    $first = app(ReservationService::class)->create($user, [
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(9, 0),
        'end_at' => now()->addDay()->setTime(12, 0),
        'motive' => 'Primera solicitud',
    ]);

    $second = app(ReservationService::class)->create($user, [
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(10, 0),
        'end_at' => now()->addDay()->setTime(13, 0),
        'motive' => 'Segunda solicitud',
    ]);

    expect($first->status)->toBe(ReservationStatus::Pendiente)
        ->and($second->status)->toBe(ReservationStatus::Pendiente);
});

it('prevents approving a pending request that now overlaps an approved one', function () {
    $approver = createUserWithRole('responsable');
    $vehicle = Vehicle::factory()->create();

    $approved = Reservation::factory()->approved($approver)->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(9, 0),
        'end_at' => now()->addDay()->setTime(12, 0),
    ]);

    $pending = Reservation::factory()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(10, 0),
        'end_at' => now()->addDay()->setTime(13, 0),
    ]);

    expect(fn () => app(ReservationService::class)->approve($pending, $approver))
        ->toThrow(ValidationException::class);

    expect($pending->fresh()->status)->toBe(ReservationStatus::Pendiente);
});

it('approves a pending request when there is no overlap', function () {
    $approver = createUserWithRole('responsable');
    $vehicle = Vehicle::factory()->create();

    $pending = Reservation::factory()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(9, 0),
        'end_at' => now()->addDay()->setTime(12, 0),
    ]);

    app(ReservationService::class)->approve($pending, $approver);

    expect($pending->fresh()->status)->toBe(ReservationStatus::Aprobada)
        ->and($pending->approved_by)->toBe($approver->id)
        ->and($pending->approved_at)->not->toBeNull();
});

it('rejects a pending reservation with an optional reason', function () {
    $approver = createUserWithRole('responsable');
    $user = createUserWithRole('chofer');
    $vehicle = Vehicle::factory()->create();

    $pending = Reservation::factory()->create([
        'user_id' => $user->id,
        'vehicle_id' => $vehicle->id,
    ]);

    app(ReservationService::class)->reject($pending, $approver, 'No hay presupuesto para el viaje.');

    expect($pending->fresh()->status)->toBe(ReservationStatus::Rechazada)
        ->and($pending->rejection_reason)->toBe('No hay presupuesto para el viaje.')
        ->and($pending->approved_by)->toBe($approver->id);
});

it('does not allow a request on a vehicle in maintenance', function () {
    $user = createUserWithRole('chofer');
    $vehicle = Vehicle::factory()->maintenance()->create();

    expect(fn () => app(ReservationService::class)->create($user, [
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(9, 0),
        'end_at' => now()->addDay()->setTime(12, 0),
        'motive' => 'Uso interno',
    ]))->toThrow(ValidationException::class);
});

it('reports a specific message when the vehicle is in maintenance', function () {
    $user = createUserWithRole('chofer');
    $vehicle = Vehicle::factory()->maintenance()->create();

    try {
        app(ReservationService::class)->create($user, [
            'vehicle_id' => $vehicle->id,
            'start_at' => now()->addDay()->setTime(9, 0),
            'end_at' => now()->addDay()->setTime(12, 0),
            'motive' => 'Uso interno',
        ]);

        $this->fail('Expected ValidationException was not thrown.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['vehicle_id'][0])
            ->toBe('El vehículo está en mantenimiento y no se puede usar en este momento.');
    }
});

it('prevents cancelling someone elses approved reservation', function () {
    $owner = createUserWithRole('chofer');
    $other = createUserWithRole('chofer');
    $vehicle = Vehicle::factory()->create();

    $reservation = Reservation::factory()->approved()->create([
        'user_id' => $owner->id,
        'vehicle_id' => $vehicle->id,
    ]);

    expect(fn () => app(ReservationService::class)->cancel($reservation, $other))->toThrow(HttpException::class);

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Aprobada);
});
