<div>
    @if (session('status'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-md">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-md">
            {{ session('error') }}
        </div>
    @endif

    @can('crear vehiculos')
        <div class="mb-4">
            <x-button wire:click="create">
                {{ __('Nuevo Vehículo') }}
            </x-button>
        </div>
    @endcan

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Placa</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marca / Modelo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Año</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacidad</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver asignado</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($vehicles as $vehicle)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $vehicle->plate }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $vehicle->brand }} {{ $vehicle->model }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $vehicle->year ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $vehicle->capacity }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($vehicle->status === \App\Enums\VehicleStatus::Disponible)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">Disponible</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">En mantenimiento</span>
                                @if ($vehicle->maintenance_start_at && $vehicle->maintenance_end_at)
                                    <span class="mt-1 block text-xs text-gray-500">
                                        {{ $vehicle->maintenance_start_at->format('d/m/y H:i') }} → {{ $vehicle->maintenance_end_at->format('d/m/y H:i') }}
                                    </span>
                                @endif
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $vehicle->driver?->name ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @can('editar vehiculos')
                                <button wire:click="edit({{ $vehicle->id }})" class="text-indigo-600 hover:text-indigo-900">Editar</button>
                            @endcan
                            @can('eliminar vehiculos')
                                <button
                                    wire:click="delete({{ $vehicle->id }})"
                                    wire:confirm="{{ __('¿Eliminar este vehículo?') }}"
                                    class="ml-3 text-red-600 hover:text-red-900"
                                >Eliminar</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                            {{ __('No hay vehículos registrados.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-dialog-modal wire:model="showModal" maxWidth="2xl">
        <x-slot name="title">
            {{ $editingId === null ? __('Nuevo Vehículo') : __('Editar Vehículo') }}
        </x-slot>

        <x-slot name="content">
            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-label value="{{ __('Placa') }}" />
                        <x-input wire:model="plate" type="text" class="mt-1 block w-full" />
                        <x-input-error for="plate" class="mt-2" />
                    </div>

                    <div>
                        <x-label value="{{ __('Marca') }}" />
                        <x-input wire:model="brand" type="text" class="mt-1 block w-full" />
                        <x-input-error for="brand" class="mt-2" />
                    </div>

                    <div>
                        <x-label value="{{ __('Modelo') }}" />
                        <x-input wire:model="model" type="text" class="mt-1 block w-full" />
                        <x-input-error for="model" class="mt-2" />
                    </div>

                    <div>
                        <x-label value="{{ __('Año') }}" />
                        <x-input wire:model="year" type="number" min="1990" max="2099" class="mt-1 block w-full" />
                        <x-input-error for="year" class="mt-2" />
                    </div>

                    <div>
                        <x-label value="{{ __('Capacidad (pasajeros)') }}" />
                        <x-input wire:model="capacity" type="number" min="1" max="60" class="mt-1 block w-full" />
                        <x-input-error for="capacity" class="mt-2" />
                    </div>

                    <div>
                        <x-label value="{{ __('Chofer asignado') }}" />
                        <select wire:model="driverId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">—</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="driverId" class="mt-2" />
                    </div>

                    <div>
                        <x-label value="{{ __('Estado') }}" />
                        <select wire:model.live="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach (\App\Enums\VehicleStatus::cases() as $vehicleStatus)
                                <option value="{{ $vehicleStatus->value }}">{{ $vehicleStatus->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="status" class="mt-2" />
                    </div>

                    @if ($status === \App\Enums\VehicleStatus::Mantenimiento->value)
                        <div class="sm:col-span-2 rounded-md border border-amber-200 bg-amber-50 p-4">
                            <p class="text-xs font-semibold text-amber-800 uppercase tracking-wide mb-3">
                                {{ __('Periodo de mantenimiento') }}
                            </p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <x-label value="{{ __('Inicio del mantenimiento') }}" />
                                    <x-input wire:model="maintenanceStartAt" type="datetime-local" class="mt-1 block w-full" />
                                    <x-input-error for="maintenanceStartAt" class="mt-2" />
                                </div>
                                <div>
                                    <x-label value="{{__('Fin estimado del mantenimiento') }}" />
                                    <x-input wire:model="maintenanceEndAt" type="datetime-local" class="mt-1 block w-full" />
                                    <x-input-error for="maintenanceEndAt" class="mt-2" />
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-amber-700">{{ __('Mientras dure el mantenimiento, el vehículo no podrá solicitarse.') }}</p>
                        </div>
                    @endif
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="close" class="mr-2">{{ __('Cancelar') }}</x-secondary-button>
            <x-button wire:click="save" wire:loading.attr="disabled">{{ __('Guardar') }}</x-button>
        </x-slot>
    </x-dialog-modal>
</div>