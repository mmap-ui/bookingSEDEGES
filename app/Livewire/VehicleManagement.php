<?php

namespace App\Livewire;

use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class VehicleManagement extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $plate = '';
    public string $brand = '';
    public string $model = '';
    public ?int $year = null;
    public int $capacity = 4;
    public string $status = VehicleStatus::Disponible->value;
    public ?int $driverId = null;
    public ?string $maintenanceStartAt = null;
    public ?string $maintenanceEndAt = null;

    use WithPagination;
    public string $search = '';


    protected function rules(): array
    {
        $isMaintenance = $this->status === VehicleStatus::Mantenimiento->value;

        return [
            'plate' => ['required', 'string', 'max:15', Rule::unique('vehicles', 'plate')->ignore($this->editingId)],
            'brand' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:60'],
            'year' => ['nullable', 'integer', 'between:1990,2099'],
            'capacity' => ['required', 'integer', 'between:1,60'],
            'status' => ['required', Rule::enum(VehicleStatus::class)],
            'driverId' => ['nullable', 'integer', 'exists:users,id'],
            'maintenanceStartAt' => [$isMaintenance ? 'required' : 'nullable', 'date'],
            'maintenanceEndAt' => [$isMaintenance ? 'required' : 'nullable', 'date', 'after:maintenanceStartAt'],
            
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    private function resetMaintenanceDates(): void
    {
        $this->maintenanceStartAt = null;
        $this->maintenanceEndAt = null;
    }

    public function create(): void
    {
        $this->authorize('crear vehiculos');

        $this->editingId = null;
        $this->resetValidation();
        $this->reset(['plate', 'brand', 'model', 'year', 'capacity', 'status', 'driverId']);
        $this->capacity = 4;
        $this->status = VehicleStatus::Disponible->value;
        $this->resetMaintenanceDates();
        $this->showModal = true;
    }

    public function edit(Vehicle $vehicle): void
    {
        $this->authorize('editar vehiculos');

        $this->editingId = $vehicle->id;
        $this->plate = $vehicle->plate;
        $this->brand = $vehicle->brand;
        $this->model = $vehicle->model;
        $this->year = $vehicle->year;
        $this->capacity = $vehicle->capacity;
        $this->status = $vehicle->status->value;
        $this->driverId = $vehicle->driver_id;
        $this->maintenanceStartAt = $vehicle->maintenance_start_at?->format('Y-m-d\TH:i');
        $this->maintenanceEndAt = $vehicle->maintenance_end_at?->format('Y-m-d\TH:i');
        $this->resetValidation();
        $this->showModal = true;
    }

    public function close(): void
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->authorize($this->editingId === null ? 'crear vehiculos' : 'editar vehiculos');

        $data = $this->validate();

        $data['driver_id'] = $data['driverId'];
        unset($data['driverId']);

        $isMaintenance = $this->status === VehicleStatus::Mantenimiento->value;
        if ($isMaintenance) {
            $data['maintenance_start_at'] = Carbon::parse($data['maintenanceStartAt']);
            $data['maintenance_end_at'] = Carbon::parse($data['maintenanceEndAt']);
        } else {
            $data['maintenance_start_at'] = null;
            $data['maintenance_end_at'] = null;
        }

        unset($data['maintenanceStartAt'], $data['maintenanceEndAt']);


        if ($this->editingId === null) {
            Vehicle::query()->create($data);
            session()->flash('status', 'Vehículo registrado correctamente.');
        } else {
            Vehicle::query()->findOrFail($this->editingId)->update($data);
            session()->flash('status', 'Vehículo actualizado correctamente.');
        }

        $this->reset(['editingId', 'plate', 'brand', 'model', 'year', 'capacity', 'status', 'driverId']);
        $this->resetMaintenanceDates();
        $this->showModal = false;
    }

    public function delete(Vehicle $vehicle): void
    {
        $this->authorize('eliminar vehiculos');

        if ($vehicle->reservations()->exists()) {
            session()->flash('error', 'No se puede eliminar un vehículo con reservas asociadas.');

            return;
        }

        $vehicle->delete();
        session()->flash('status', 'Vehículo eliminado.');
    }

    public function render()
    {
        return view('livewire.vehicle-management', [
            'vehicles' => Vehicle::query()
                ->with('driver')

                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('plate', 'like', '%' . $this->search . '%')
                            ->orWhere('brand', 'like', '%' . $this->search . '%')->orWhere('year', 'like', '%' . $this->search . '%')
                            ->orWhere('capacity', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderBy('plate')
                ->paginate(15),

            'drivers' => User::query()
                ->whereHas('roles', fn($query) => $query->where('name', 'chofer'))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
