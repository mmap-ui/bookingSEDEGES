<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReportService;
use Carbon\Carbon;
use Livewire\Component;

class Reports extends Component
{
    public string $start;

    public string $end;

    public ?int $vehicleId = null;

    public ?int $driverId = null;

    public function mount(): void
    {
        $this->start = now()->subDays(30)->format('Y-m-d');
        $this->end = now()->format('Y-m-d');
    }

    public function render()
    {
        $start = filled($this->start) ? Carbon::parse($this->start)->startOfDay() : null;
        $end = filled($this->end) ? Carbon::parse($this->end)->endOfDay() : null;

        $hasRange = $start !== null && $end !== null && $start->lessThanOrEqualTo($end);

        if ($hasRange) {
            $usageRows = app(ReportService::class)->usageRows(
                $start,
                $end,
                $this->vehicleId,
                $this->driverId,
            );
        } else {
            $usageRows = [];
        }

        return view('livewire.reports', [
            'usageRows' => collect($usageRows),
            'activeMaintenances' => app(ReportService::class)->activeMaintenances(),
            'rangeStart' => $start,
            'rangeEnd' => $end,
            'hasRange' => $hasRange,
            'vehicles' => Vehicle::query()->orderBy('plate')->get(['id', 'plate', 'brand', 'model']),
            'drivers' => User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', 'chofer'))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
