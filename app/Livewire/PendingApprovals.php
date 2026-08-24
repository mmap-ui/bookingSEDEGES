<?php

namespace App\Livewire;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class PendingApprovals extends Component
{
    use WithPagination;

    public ?int $rejectingId = null;

    public ?string $rejectionReason = null;

    protected $listeners = ['reservation-created' => 'refresh', 'reservation-status-updated' => 'refresh'];

    public function approve(Reservation $reservation): void
    {
        $this->authorize('aprobar reservas');

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

    public function startRejection(Reservation $reservation): void
    {
        $this->authorize('aprobar reservas');

        $this->rejectingId = $reservation->id;
        $this->rejectionReason = null;
    }

    public function cancelRejection(): void
    {
        $this->rejectingId = null;
        $this->rejectionReason = null;
    }

    public function confirmReject(): void
    {
        $this->authorize('aprobar reservas');

        $this->validate([
            'rejectingId' => ['required', 'integer'],
            'rejectionReason' => ['nullable', 'string', 'max:500'],
        ]);

        $reservation = Reservation::findOrFail($this->rejectingId);

        app(ReservationService::class)->reject($reservation, auth()->user(), $this->rejectionReason);

        $this->reset(['rejectingId', 'rejectionReason']);
        $this->dispatch('calendar-filters-updated');
        $this->dispatch('reservation-status-updated');
        session()->flash('status', 'Reserva rechazada.');
    }

    public function refresh(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $pendingReservations = Reservation::query()
            ->with(['user', 'vehicle'])
            ->where('status', ReservationStatus::Pendiente)
            ->orderBy('start_at')
            ->paginate(10);

        return view('livewire.pending-approvals', [
            'pendingReservations' => $pendingReservations,
            'pendingConflicts' => $this->pendingConflicts(),
        ]);
    }

    /**
     * Groups pending reservations of the same vehicle whose time ranges
     * overlap, so the approver can compare them and decide the priority.
     *
     * @return array<int, array<int, Reservation>>
     */
    private function pendingConflicts(): array
    {
        $pending = Reservation::query()
            ->with(['user', 'vehicle'])
            ->where('status', ReservationStatus::Pendiente)
            ->orderBy('start_at')
            ->get();

        $conflicts = [];

        foreach ($pending as $reservation) {
            $overlapping = $pending
                ->reject(fn (Reservation $other) => $other->id === $reservation->id)
                ->filter(
                    fn (Reservation $other) => $other->vehicle_id === $reservation->vehicle_id
                        && $other->start_at < $reservation->end_at
                        && $other->end_at > $reservation->start_at
                )
                ->values();

            if ($overlapping->isNotEmpty()) {
                $conflicts[$reservation->id] = $overlapping->all();
            }
        }

        return $conflicts;
    }
}
