<?php

namespace App\Livewire;

use App\Models\Vehicle;
use App\Rules\NoApprovedReservationOverlap;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class ReservationForm extends Component
{
    public bool $showModal = false;

    public ?int $vehicleId = null;

    public ?string $startAt = null;

    public ?string $endAt = null;

    public string $motive = '';

    public ?string $destination = null;

    protected function rules(): array
    {
        return [
            'vehicleId' => [
                'required',
                'integer',
                'exists:vehicles,id',
                new NoApprovedReservationOverlap($this->vehicleId, $this->startAt, $this->endAt),
            ],
            'startAt' => ['required', 'date', 'after_or_equal:today'],
            'endAt' => ['required', 'date', 'after:startAt'],
            'motive' => ['required', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
        ];
    }

    #[On('open-reservation-form')]
    public function open(?string $start = null, ?string $end = null): void
    {
        $this->authorize('crear reservas');

        $this->resetValidation();
        $this->startAt = filled($start)
            ? Carbon::parse($start)->format('Y-m-d\TH:i')
            : null;
        $this->endAt = filled($end)
            ? $this->normalizeEnd(Carbon::parse($end))
            : null;
        $this->motive = '';
        $this->destination = null;
        $this->showModal = true;
    }

    /**
     * FullCalendar reports range ends as an exclusive next-day midnight for
     * whole-day selections; roll that back so the form shows the last
     * selected day instead of the following midnight.
     */
    private function normalizeEnd(Carbon $end): string
    {
        if ($end->format('H:i') === '00:00') {
            $end->subMinute();
        }

        return $end->format('Y-m-d\TH:i');
    }

    public function close(): void
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    public function submit(): void
    {
        $this->authorize('crear reservas');

        $data = $this->validate();

        try {
            app(ReservationService::class)->create(auth()->user(), [
                'vehicle_id' => $data['vehicleId'],
                'start_at' => $data['startAt'],
                'end_at' => $data['endAt'],
                'motive' => $data['motive'],
                'destination' => $data['destination'],
            ]);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->errors());

            return;
        }

        $this->reset(['vehicleId', 'startAt', 'endAt', 'motive', 'destination']);
        $this->showModal = false;
        $this->dispatch('reservation-created');
        $this->dispatch('calendar-filters-updated');
        session()->flash('status', 'Solicitud de reserva registrada correctamente.');
    }

    public function render()
    {
        return view('livewire.reservation-form', [
            'vehicles' => Vehicle::query()
                ->orderBy('plate')
                ->get(),
        ]);
    }
}
