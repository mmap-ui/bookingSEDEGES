<div class="space-y-6">
    <div class="bg-white rounded-lg shadow p-6">
        <form wire:submit.prevent class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5 items-end">
            <div>
                <x-label value="{{ __('Desde') }}" />
                <x-input type="date" wire:model="start" class="mt-1 block w-full" />
                <x-input-error for="start" class="mt-2" />
            </div>
            <div>
                <x-label value="{{ __('Hasta') }}" />
                <x-input type="date" wire:model.blur="end" class="mt-1 block w-full" />
                <x-input-error for="end" class="mt-2" />
            </div>
            <div>
                <x-label value="{{ __('Vehículo') }}" />
                <select wire:model="vehicleId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('Todos los vehículos') }}</option>
                    @foreach ($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}">{{ $vehicle->plate }} · {{ $vehicle->brand }} {{ $vehicle->model }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-label value="{{ __('Chofer solicitante') }}" />
                <select wire:model="driverId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('Todos los choferes') }}</option>
                    @foreach ($drivers as $driver)
                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-button type="submit">{{ __('Consultar') }}</x-button>
            </div>
        </form>

        @if ($hasRange)
            <div class="mt-4 pt-4 border-t border-gray-100 flex justify-end">
                <a
                    href="{{ route('reportes.pdf', ['start' => $rangeStart?->format('Y-m-d'), 'end' => $rangeEnd?->format('Y-m-d'), 'vehicle' => $vehicleId, 'driver' => $driverId]) }}"
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none transition"
                >
                    {{ __('Exportar PDF') }}
                </a>
            </div>
        @endif
    </div>

    @if ($hasRange)
        @php
            $totalReservations = $usageRows->sum(fn ($row) => $row['occupations']->count());
            $totalHours = round($usageRows->sum(fn ($row) => $row['reservedSeconds']) / 3600, 1);
            $activeVehicles = $usageRows->filter(fn ($row) => $row['occupations']->isNotEmpty())->count();
        @endphp

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-500">{{ __('Reservas en el rango') }}</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalReservations }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-500">{{ __('Horas totales reservadas') }}</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalHours }} h</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-500">{{ __('Vehículos con actividad') }}</p>
                <p class="mt-1 text-3xl font-bold text-emerald-600">{{ $activeVehicles }}</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Vehículo') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Chofer asignado') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Horas reservadas') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Reservas en el rango') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($usageRows as $row)
                        @php
                            $reservedHours = round($row['reservedSeconds'] / 3600, 1);
                        @endphp
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm font-semibold text-gray-900">{{ $row['vehicle']->plate }}</p>
                                <p class="text-xs text-gray-500">{{ $row['vehicle']->brand }} {{ $row['vehicle']->model }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{ $row['vehicle']->driver?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $reservedHours }} h</td>
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    @forelse ($row['occupations'] as $reservation)
                                        <p class="text-xs text-gray-600">
                                            {{ $reservation->start_at->format('d/m H:i') }} → {{ $reservation->end_at->format('d/m H:i') }}
                                            <span class="text-gray-400">· {{ $reservation->user->name }}</span>
                                            @if ($reservation->destination)
                                                <span class="text-gray-400">· {{ __('Traslado a') }} {{ $reservation->destination }}</span>
                                            @endif
                                        </p>
                                    @empty
                                        <p class="text-xs text-gray-400">—</p>
                                    @endforelse
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif (filled($this->start) && filled($this->end))
        <div class="bg-white rounded-lg shadow p-6 text-center text-sm text-red-600">
            {{ __('El rango de fechas no es válido: la fecha "desde" debe ser anterior o igual a "hasta".') }}
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-12 text-center text-gray-500">
            {{ __('Seleccione un rango de fechas para consultar la disponibilidad.') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 pb-4">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">{{ __('Mantenimientos activos') }} ({{ $activeMaintenances->count() }})</h3>
        </div>
        @if ($activeMaintenances->isEmpty())
            <p class="px-6 pb-6 text-sm text-gray-500">{{ __('Ningún vehículo se encuentra en mantenimiento actualmente.') }}</p>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Vehículo') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Inicio') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Fin estimado') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Chofer asignado') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($activeMaintenances as $vehicle)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm font-semibold text-gray-900">{{ $vehicle->plate }}</p>
                                <p class="text-xs text-gray-500">{{ $vehicle->brand }} {{ $vehicle->model }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $vehicle->maintenance_start_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $vehicle->maintenance_end_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $vehicle->driver?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
