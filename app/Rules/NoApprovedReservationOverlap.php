<?php

namespace App\Rules;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoApprovedReservationOverlap implements ValidationRule
{
    public function __construct(
        private readonly mixed $vehicleId = null,
        private readonly mixed $startAt = null,
        private readonly mixed $endAt = null,
        private readonly ?int $ignoreId = null,
    ) {}

    /**
     * Rejects slots that overlap an already approved reservation for the vehicle.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $start = $this->startAt ? Carbon::parse($this->startAt) : null;
        $end = $this->endAt ? Carbon::parse($this->endAt) : null;
        $vehicleId = $this->vehicleId ?? $value;

        if ($start === null || $end === null || $start->greaterThanOrEqualTo($end)) {
            return;
        }

        $overlaps = Reservation::query()
            ->where('vehicle_id', $vehicleId)
            ->where('status', ReservationStatus::Aprobada)
            ->when($this->ignoreId, fn ($query) => $query->where('id', '!=', $this->ignoreId))
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->exists();

        if ($overlaps) {
            $fail('El vehículo ya se encuentra reservado dentro del rango de fecha y hora solicitado.');
        }
    }
}
