<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CalendarEventController extends Controller
{
    /**
     * Returns reservations as FullCalendar JSON events.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $query = Reservation::query()
            ->with(['vehicle', 'user'])
            ->whereIn('status', [ReservationStatus::Aprobada, ReservationStatus::Pendiente])
            ->when($request->filled('vehicle'), fn ($query) => $query->where('vehicle_id', $request->integer('vehicle')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when(
                $request->filled('start') && $request->filled('end'),
                fn ($query) => $query
                    ->where('start_at', '<', $request->date('end'))
                    ->where('end_at', '>', $request->date('start'))
            );

        $reservations = $query->orderBy('start_at')->get();

        $pendingByVehicle = Reservation::query()
            ->with('user')
            ->where('status', ReservationStatus::Pendiente)
            ->whereIn('vehicle_id', $reservations->pluck('vehicle_id')->unique())
            ->get()
            ->groupBy('vehicle_id');

        $events = $reservations->map(function (Reservation $reservation) use ($pendingByVehicle): array {
            return [
                'id' => 'reservation-'.$reservation->id,
                'title' => sprintf('(%s) %s', $reservation->vehicle->plate, $reservation->motive),
                'start' => $reservation->start_at->toIso8601String(),
                'end' => $reservation->end_at->toIso8601String(),
                'color' => $reservation->status === ReservationStatus::Aprobada ? '#059669' : '#d97706',
                'extendedProps' => [
                    'id' => $reservation->id,
                    'status' => $reservation->status->value,
                    'statusLabel' => $reservation->status->label(),
                    'vehicle' => $reservation->vehicle->plate,
                    'vehicleId' => $reservation->vehicle_id,
                    'requester' => $reservation->user->name,
                    'requesterId' => $reservation->user_id,
                    'motive' => $reservation->motive,
                    'destination' => $reservation->destination,
                    'conflicts' => $this->pendingConflictsFor($reservation, $pendingByVehicle),
                ],
            ];
        });

        return response()->json($events);
    }

    /**
     * Other pending requests for the same vehicle whose time ranges
     * overlap this reservation, so approvers can compare priorities.
     *
     * @param  Collection<int, Reservation>  $pendingByVehicle
     * @return array<int, array<string, string>>
     */
    private function pendingConflictsFor(Reservation $reservation, $pendingByVehicle): array
    {
        if ($reservation->status !== ReservationStatus::Pendiente) {
            return [];
        }

        return $pendingByVehicle
            ->get($reservation->vehicle_id, collect())
            ->reject(fn (Reservation $other) => $other->id === $reservation->id)
            ->filter(
                fn (Reservation $other) => $other->start_at < $reservation->end_at
                    && $other->end_at > $reservation->start_at
            )
            ->map(fn (Reservation $other): array => [
                'id' => $other->id,
                'requester' => $other->user->name,
                'motive' => $other->motive,
                'start' => $other->start_at->format('d/m/Y H:i'),
                'end' => $other->end_at->format('d/m/Y H:i'),
            ])
            ->values()
            ->all();
    }
}
