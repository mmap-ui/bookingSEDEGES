<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Enums\VehicleStatus;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    /**
     * Create a pending reservation, guarding against double-booking in a race
     * safe manner by locking the vehicle row within a transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Reservation
    {
        $data['start_at'] = Carbon::parse($data['start_at']);
        $data['end_at'] = Carbon::parse($data['end_at']);

        $this->assertVehicleAvailable($data['vehicle_id']);

        return DB::transaction(function () use ($user, $data): Reservation {
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($data['vehicle_id']);

            $this->assertNoOverlap($vehicle->id, $data['start_at'], $data['end_at']);

            return $vehicle->reservations()->create([
                ...$data,
                'user_id' => $user->id,
                'status' => ReservationStatus::Pendiente,
            ]);
        }, attempts: 5);
    }

    /**
     * Approve a pending reservation, re-checking the schedule to avoid
     * approving two requests that now overlap.
     */
    public function approve(Reservation $reservation, User $approver): void
    {
        $this->assertPending($reservation);

        DB::transaction(function () use ($reservation, $approver): void {
            $vehicle = $reservation->vehicle()->lockForUpdate()->first();

            if ($vehicle === null || $vehicle->status !== VehicleStatus::Disponible) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'El vehículo no se encuentra disponible en este momento.',
                ]);
            }

            $this->assertNoOverlap(
                $reservation->vehicle_id,
                $reservation->start_at,
                $reservation->end_at,
                ignoreId: $reservation->id
            );

            $reservation->update([
                'status' => ReservationStatus::Aprobada,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);
        });
    }

    /**
     * Reject a pending reservation with an optional reason.
     */
    public function reject(Reservation $reservation, User $approver, ?string $reason = null): void
    {
        $this->assertPending($reservation);

        $reservation->update([
            'status' => ReservationStatus::Rechazada,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => filled($reason) ? $reason : null,
        ]);
    }

    /**
     * Cancel a reservation that has not yet been resolved.
     */
    public function cancel(Reservation $reservation, User $user): void
    {
        if (! in_array($reservation->status, [ReservationStatus::Pendiente, ReservationStatus::Aprobada], true)) {
            throw new DomainException('Solo se pueden cancelar solicitudes pendientes o aprobadas.');
        }

        if ($reservation->user_id !== $user->id && ! $user->hasPermissionTo('aprobar reservas')) {
            abort(403);
        }

        $reservation->update(['status' => ReservationStatus::Cancelada]);
    }

    /**
     * Assert that no approved reservation overlaps the given slot.
     * Pending requests do not block each other; conflicts are resolved at
     * approval time inside approve().
     */
    public function assertNoOverlap(
        int $vehicleId,
        Carbon|CarbonInterface $start,
        Carbon|CarbonInterface $end,
        ?int $ignoreId = null,
    ): void {
        $start = $start instanceof Carbon ? $start : Carbon::parse($start);
        $end = $end instanceof Carbon ? $end : Carbon::parse($end);

        $overlaps = Reservation::query()
            ->where('vehicle_id', $vehicleId)
            ->where('status', ReservationStatus::Aprobada)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'El vehículo ya se encuentra reservado dentro del rango de fecha y hora solicitado.',
            ]);
        }
    }

    private function assertVehicleAvailable(int $vehicleId): void
    {
        $vehicle = Vehicle::find($vehicleId);

        if ($vehicle === null) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'El vehículo seleccionado no existe.',
            ]);
        }

        if ($vehicle->status === VehicleStatus::Mantenimiento) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'El vehículo está en mantenimiento y no se puede usar en este momento.',
            ]);
        }

        if ($vehicle->status !== VehicleStatus::Disponible) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'El vehículo seleccionado no se encuentra disponible.',
            ]);
        }
    }

    private function assertPending(Reservation $reservation): void
    {
        if ($reservation->status !== ReservationStatus::Pendiente) {
            throw new DomainException('La solicitud ya fue resuelta.');
        }
    }
}
