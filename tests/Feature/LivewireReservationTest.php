<?php

use App\Enums\ReservationStatus;
use App\Livewire\Calendar;
use App\Livewire\PendingApprovals;
use App\Livewire\ReservationForm;
use App\Models\Reservation;
use App\Models\Vehicle;
use Livewire\Livewire;

it('shows an overlap validation error when creating a conflicting request', function () {
    $user = createUserWithRole('chofer');
    $vehicle = Vehicle::factory()->create();

    Reservation::factory()->approved()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(9, 0),
        'end_at' => now()->addDay()->setTime(12, 0),
    ]);

    Livewire::actingAs($user)
        ->test(ReservationForm::class)
        ->call('open', start: now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i'), end: now()->addDay()->setTime(13, 0)->format('Y-m-d\TH:i'))
        ->set('motive', 'Reunión de trabajo')
        ->call('submit')
        ->assertHasErrors(['vehicleId']);
});

it('creates a reservation through the livewire form', function () {
    $user = createUserWithRole('chofer');
    $vehicle = Vehicle::factory()->create();

    Livewire::actingAs($user)
        ->test(ReservationForm::class)
        ->call('open', start: now()->addDay()->setTime(14, 0)->format('Y-m-d\TH:i'), end: now()->addDay()->setTime(17, 0)->format('Y-m-d\TH:i'))
        ->set('vehicleId', $vehicle->id)
        ->set('motive', 'Traslado a oficina central')
        ->set('destination', 'La Paz')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDispatched('reservation-created');

    expect(Reservation::where('user_id', $user->id)->count())->toBe(1)
        ->and(Reservation::first()->status)->toBe(ReservationStatus::Pendiente);
});

it('only allows users with the create permission to submit', function () {
    $user = createUserWithRole('consultas');

    Livewire::actingAs($user)
        ->test(ReservationForm::class)
        ->call('open')
        ->assertForbidden();
});

it('approves a pending reservation from the approvals panel', function () {
    $approver = createUserWithRole('responsable');
    $vehicle = Vehicle::factory()->create();
    $pending = Reservation::factory()->create(['vehicle_id' => $vehicle->id]);

    Livewire::actingAs($approver)
        ->test(PendingApprovals::class)
        ->call('approve', $pending->id)
        ->assertHasNoErrors()
        ->assertDispatched('reservation-status-updated');

    expect($pending->fresh()->status)->toBe(ReservationStatus::Aprobada);
});

it('cannot approve with the approver overlapping another approved reservation', function () {
    $approver = createUserWithRole('responsable');
    $vehicle = Vehicle::factory()->create();

    Reservation::factory()->approved($approver)->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(9, 0),
        'end_at' => now()->addDay()->setTime(12, 0),
    ]);

    $pending = Reservation::factory()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(10, 0),
        'end_at' => now()->addDay()->setTime(13, 0),
    ]);

    Livewire::actingAs($approver)
        ->test(PendingApprovals::class)
        ->call('approve', $pending->id);

    expect($pending->fresh()->status)->toBe(ReservationStatus::Pendiente);
});

it('rejects a pending reservation with a reason', function () {
    $approver = createUserWithRole('responsable');
    $vehicle = Vehicle::factory()->create();
    $pending = Reservation::factory()->create(['vehicle_id' => $vehicle->id]);

    Livewire::actingAs($approver)
        ->test(PendingApprovals::class)
        ->call('startRejection', $pending->id)
        ->set('rejectionReason', 'Falta autorización')
        ->call('confirmReject')
        ->assertHasNoErrors();

    expect($pending->fresh()->status)->toBe(ReservationStatus::Rechazada)
        ->and($pending->fresh()->rejection_reason)->toBe('Falta autorización');
});

it('lets the owner cancel their own reservation from the calendar', function () {
    $owner = createUserWithRole('chofer');
    $vehicle = Vehicle::factory()->create();
    $reservation = Reservation::factory()->approved()->create([
        'user_id' => $owner->id,
        'vehicle_id' => $vehicle->id,
    ]);

    Livewire::actingAs($owner)
        ->test(Calendar::class)
        ->dispatch('request-reservation-cancel', reservationId: $reservation->id)
        ->assertDispatched('reservation-status-updated');

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Cancelada);
});

it('lets an approver cancel any reservation from the calendar', function () {
    $owner = createUserWithRole('chofer');
    $approver = createUserWithRole('responsable');
    $vehicle = Vehicle::factory()->create();
    $reservation = Reservation::factory()->approved()->create([
        'user_id' => $owner->id,
        'vehicle_id' => $vehicle->id,
    ]);

    Livewire::actingAs($approver)
        ->test(Calendar::class)
        ->dispatch('request-reservation-cancel', reservationId: $reservation->id)
        ->assertDispatched('reservation-status-updated');

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Cancelada);
});

it('does not let a stranger cancel a reservation from the calendar', function () {
    $owner = createUserWithRole('chofer');
    $stranger = createUserWithRole('consultas');
    $vehicle = Vehicle::factory()->create();
    $reservation = Reservation::factory()->approved()->create([
        'user_id' => $owner->id,
        'vehicle_id' => $vehicle->id,
    ]);

    Livewire::actingAs($stranger)
        ->test(Calendar::class)
        ->dispatch('request-reservation-cancel', reservationId: $reservation->id);

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Aprobada);
});

it('cannot cancel an already resolved reservation', function () {
    $approver = createUserWithRole('responsable');
    $vehicle = Vehicle::factory()->create();
    $reservation = Reservation::factory()->rejected()->create([
        'vehicle_id' => $vehicle->id,
    ]);

    Livewire::actingAs($approver)
        ->test(Calendar::class)
        ->dispatch('request-reservation-cancel', reservationId: $reservation->id);

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Rechazada);
});

it('approves a pending reservation from the calendar details panel', function () {
    $approver = createUserWithRole('responsable');
    $vehicle = Vehicle::factory()->create();
    $pending = Reservation::factory()->create(['vehicle_id' => $vehicle->id]);

    Livewire::actingAs($approver)
        ->test(Calendar::class)
        ->dispatch('request-reservation-approve', reservationId: $pending->id)
        ->assertDispatched('reservation-status-updated');

    expect($pending->fresh()->status)->toBe(ReservationStatus::Aprobada);
});

it('does not let a user without approval permission approve from the calendar', function () {
    $user = createUserWithRole('chofer');
    $vehicle = Vehicle::factory()->create();
    $pending = Reservation::factory()->create(['vehicle_id' => $vehicle->id]);

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->dispatch('request-reservation-approve', reservationId: $pending->id);

    expect($pending->fresh()->status)->toBe(ReservationStatus::Pendiente);
});

it('shows overlapping pending requests as conflicts in the approvals panel', function () {
    $approver = createUserWithRole('responsable');
    $vehicle = Vehicle::factory()->create();
    $otherVehicle = Vehicle::factory()->create();

    $first = Reservation::factory()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(9, 0),
        'end_at' => now()->addDay()->setTime(12, 0),
    ]);

    $second = Reservation::factory()->create([
        'vehicle_id' => $vehicle->id,
        'start_at' => now()->addDay()->setTime(10, 0),
        'end_at' => now()->addDay()->setTime(13, 0),
    ]);

    $unrelated = Reservation::factory()->create([
        'vehicle_id' => $otherVehicle->id,
        'start_at' => now()->addDay()->setTime(10, 0),
        'end_at' => now()->addDay()->setTime(13, 0),
    ]);

    Livewire::actingAs($approver)
        ->test(PendingApprovals::class)
        ->assertViewHas('pendingConflicts', function (array $conflicts) use ($first, $second, $unrelated) {
            $conflictingIds = collect($conflicts[$first->id] ?? [])->pluck('id');

            return $conflictingIds->contains($second->id)
                && ! isset($conflicts[$unrelated->id]);
        })
        ->assertSee(__('Horarios superpuestos'));
});

it('disables vehicles under maintenance in the reservation form', function () {
    $user = createUserWithRole('chofer');
    Vehicle::factory()->maintenance()->create(['plate' => '1234ABC']);

    Livewire::actingAs($user)
        ->test(ReservationForm::class)
        ->assertSee(__('En mantenimiento, no se puede usar en este momento'))
        ->assertSeeHtml('disabled');
});

it('normalizes calendar selection dates for the reservation form', function () {
    $user = createUserWithRole('chofer');

    Livewire::actingAs($user)
        ->test(ReservationForm::class)
        ->call('open', start: '2026-08-21T00:00:00-04:00', end: '2026-08-22T00:00:00-04:00')
        ->assertSet('startAt', '2026-08-21T00:00')
        ->assertSet('endAt', '2026-08-21T23:59');
});

it('keeps exact times when normalizing calendar selection dates', function () {
    $user = createUserWithRole('chofer');

    Livewire::actingAs($user)
        ->test(ReservationForm::class)
        ->call('open', start: '2026-08-21T10:00:00', end: '2026-08-21T13:00:00')
        ->assertSet('startAt', '2026-08-21T10:00')
        ->assertSet('endAt', '2026-08-21T13:00');
});
