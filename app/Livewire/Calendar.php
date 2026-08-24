<?php

namespace App\Livewire;

use App\Models\Reservation;
use App\Models\Vehicle;
use App\Services\ReservationService;
use DomainException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class Calendar extends Component
{
    public ?int $vehicleId = null;

    public string $status = 'todas';

    public function updatedVehicleId(): void
    {
        $this->dispatch('calendar-filters-updated');
    }

    public function updatedStatus(): void
    {
        $this->dispatch('calendar-filters-updated');
    }

    #[On('request-reservation-approve')]
    public function approveReservation(int $reservationId): void
    {
        if (! auth()->user()->hasPermissionTo('aprobar reservas')) {
            session()->flash('error', 'No tienes permiso para aprobar reservas.');

            return;
        }

        $reservation = Reservation::findOrFail($reservationId);

        try {
            app(ReservationService::class)->approve($reservation, auth()->user());
        } catch (ValidationException $exception) {
            session()->flash('error', collect($exception->errors())->flatten()->first());

            return;
        }

        $this->dispatch('calendar-filters-updated');
        $this->dispatch('reservation-status-updated');
        session()->flash('status', 'Reserva aprobada correctamente.');
    }

    #[On('request-reservation-cancel')]
    public function cancelReservation(int $reservationId): void
    {
        $reservation = Reservation::findOrFail($reservationId);
        $user = auth()->user();

        if ($reservation->user_id !== $user->id && ! $user->hasPermissionTo('aprobar reservas')) {
            session()->flash('error', 'No tienes permiso para cancelar esta reserva.');

            return;
        }

        try {
            app(ReservationService::class)->cancel($reservation, $user);
        } catch (DomainException $exception) {
            session()->flash('error', $exception->getMessage());

            return;
        }

        $this->dispatch('calendar-filters-updated');
        $this->dispatch('reservation-status-updated');
        session()->flash('status', 'Reserva cancelada correctamente.');
    }

    public function render()
    {
        return view('livewire.calendar', [
            'vehicles' => Vehicle::query()
                ->orderBy('plate')
                ->get(),
        ]);
    }
}
