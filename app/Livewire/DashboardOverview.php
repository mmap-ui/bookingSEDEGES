<?php

namespace App\Livewire;

use App\Enums\ReservationStatus;
use App\Enums\VehicleStatus;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Livewire\Component;

class DashboardOverview extends Component
{
    private const HIGH_USAGE_THRESHOLD = 8;

    private User $user;

    public function mount(): void
    {
        $this->user = auth()->user();
    }

    public function canApprove(): bool
    {
        return $this->user->hasAnyRole(['administrador', 'responsable']);
    }

    public function isDriver(): bool
    {
        return $this->user->hasRole('chofer');
    }

    public function render()
    {
        $dayStart = today()->startOfDay();
        $dayEnd = today()->endOfDay();
        $now = now();

        $scheduledToday = Reservation::query()
            ->with(['vehicle', 'user'])
            ->approved()
            ->where('start_at', '<=', $dayEnd)
            ->where('end_at', '>=', $dayStart)
            ->orderBy('start_at')
            ->get();

        $scheduledVehicleIds = $scheduledToday->pluck('vehicle_id');

        $freeVehicles = Vehicle::query()
            ->available()
            ->whereNotIn('id', $scheduledVehicleIds)
            ->orderBy('plate')
            ->get();

        $maintenanceVehicles = Vehicle::query()
            ->where('status', VehicleStatus::Mantenimiento)
            ->orderBy('plate')
            ->get();

        return view('livewire.dashboard-overview', [
            'scheduledToday' => $scheduledToday,
            'freeVehicles' => $freeVehicles,
            'maintenanceVehicles' => $maintenanceVehicles,
            'inProgressNow' => $scheduledToday->filter(
                fn (Reservation $reservation) => $reservation->start_at <= $now && $reservation->end_at >= $now
            )->values(),
            'upcomingThisWeek' => $this->upcomingThisWeek(),
            'pendingApprovalsCount' => $this->canApprove()
                ? Reservation::query()->where('status', ReservationStatus::Pendiente)->count()
                : 0,
            'driverStats' => $this->isDriver() ? $this->driverStats() : null,
            'adminStats' => $this->canApprove() ? $this->adminStats() : null,
            'vehicleUsage' => $this->canApprove() ? $this->vehicleUsage() : collect(),
            'highUsageThreshold' => self::HIGH_USAGE_THRESHOLD,
        ]);
    }

    /**
     * Approved reservations starting within the next 7 days.
     */
    private function upcomingThisWeek()
    {
        return Reservation::query()
            ->with(['vehicle', 'user'])
            ->approved()
            ->where('start_at', '>', now())
            ->where('start_at', '<=', now()->addDays(7))
            ->orderBy('start_at')
            ->limit(6)
            ->get();
    }

    /**
     * @return array<string, int>
     */
    private function driverStats(): array
    {
        return [
            ReservationStatus::Pendiente->value => $this->countUserReservations(ReservationStatus::Pendiente),
            ReservationStatus::Aprobada->value => $this->countUserReservations(ReservationStatus::Aprobada),
            ReservationStatus::Rechazada->value => $this->countUserReservations(ReservationStatus::Rechazada),
            ReservationStatus::Cancelada->value => $this->countUserReservations(ReservationStatus::Cancelada),
        ];
    }

    private function countUserReservations(ReservationStatus $status): int
    {
        return Reservation::query()
            ->where('user_id', $this->user->id)
            ->where('status', $status)
            ->count();
    }

    /**
     * @return array<string, int>
     */
    private function adminStats(): array
    {
        $counts = Reservation::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            ReservationStatus::Pendiente->value => (int) ($counts[ReservationStatus::Pendiente->value] ?? 0),
            ReservationStatus::Aprobada->value => (int) ($counts[ReservationStatus::Aprobada->value] ?? 0),
            ReservationStatus::Rechazada->value => (int) ($counts[ReservationStatus::Rechazada->value] ?? 0),
        ];
    }

    /**
     * Approved reservations per vehicle over the last 30 days, to spot
     * heavily used vehicles that may need maintenance.
     */
    private function vehicleUsage()
    {
        $since = Carbon::now()->subDays(30);

        return Vehicle::query()
            ->withCount([
                'reservations as approved_reservations_30d' => fn ($query) => $query
                    ->where('status', ReservationStatus::Aprobada)
                    ->where('start_at', '>=', $since),
            ])
            ->orderByDesc('approved_reservations_30d')
            ->orderBy('plate')
            ->get();
    }
}
