<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Enums\VehicleStatus;
use App\Models\Reservation;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Per-vehicle usage inside the given range, optionally filtered by
     * vehicle and by the requesting driver.
     *
     * @return array<int, array{vehicle: Vehicle, occupations: Collection, reservedSeconds: int}>
     */
    public function usageRows(
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $vehicleId = null,
        ?int $driverId = null,
    ): array {
        $reservations = Reservation::query()
            ->with('user')
            ->where('status', ReservationStatus::Aprobada)
            ->where('start_at', '<', $end->copy()->addSecond())
            ->where('end_at', '>', $start)
            ->when($vehicleId, fn ($query) => $query->where('vehicle_id', $vehicleId))
            ->when($driverId, fn ($query) => $query->where('user_id', $driverId))
            ->orderBy('start_at')
            ->get();

        return Vehicle::query()
            ->with('driver')
            ->when($vehicleId, fn ($query) => $query->where('id', $vehicleId))
            ->orderBy('plate')
            ->get()
            ->map(function (Vehicle $vehicle) use ($reservations, $start, $end): array {
                $occupations = $reservations->where('vehicle_id', $vehicle->id)->values();

                // Carbon 3's diffInSeconds is signed; always go from the
                // clipped start towards the clipped end to stay positive.
                $reservedSeconds = $occupations->sum(
                    fn (Reservation $reservation) => (int) round(
                        $reservation->start_at
                            ->max($start)
                            ->diffInSeconds($reservation->end_at->min($end))
                    )
                );

                return [
                    'vehicle' => $vehicle,
                    'occupations' => $occupations,
                    'reservedSeconds' => (int) $reservedSeconds,
                ];
            })
            ->all();
    }

    /**
     * Vehicles currently under maintenance with their scheduled period.
     */
    public function activeMaintenances(): Collection
    {
        return Vehicle::query()
            ->with('driver')
            ->where('status', VehicleStatus::Mantenimiento)
            ->orderBy('plate')
            ->get();
    }
}
