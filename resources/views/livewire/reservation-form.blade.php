<x-dialog-modal wire:model="showModal" maxWidth="2xl">
    <x-slot name="title">
        {{ __('Solicitar Reserva de Vehículo') }}
    </x-slot>

    <x-slot name="content">
        <form wire:submit="submit" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-label value="{{ __('Vehículo') }}" />
                    <select
                        wire:model="vehicleId"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">{{ __('Seleccione un vehículo') }}</option>
                        @foreach ($vehicles as $vehicle)
                            @if ($vehicle->status->isMaintenance())
                                <option value="{{ $vehicle->id }}" disabled class="text-gray-400">
                                    {{ $vehicle->plate }} · {{ $vehicle->brand }} {{ $vehicle->model }}
                                    — {{ __('En mantenimiento, no se puede usar en este momento') }}
                                </option>
                            @else
                                <option value="{{ $vehicle->id }}">
                                    {{ $vehicle->plate }} · {{ $vehicle->brand }} {{ $vehicle->model }}
                                    ({{ $vehicle->capacity }} {{ __('pasajeros') }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <x-input-error for="vehicleId" class="mt-2" />
                </div>

                <div>
                    <x-label value="{{ __('Destino') }}" />
                    <x-input
                        wire:model="destination"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="{{ __('Ej. Plaza 14 de Septiembre, Cochabamba') }}"
                    />
                    <x-input-error for="destination" class="mt-2" />
                </div>

                <div>
                    <x-label value="{{ __('Fecha y hora de inicio') }}" />
                    <x-input type="datetime-local" wire:model="startAt" class="mt-1 block w-full" />
                    <x-input-error for="startAt" class="mt-2" />
                </div>

                <div>
                    <x-label value="{{ __('Fecha y hora de fin') }}" />
                    <x-input type="datetime-local" wire:model="endAt" class="mt-1 block w-full" />
                    <x-input-error for="endAt" class="mt-2" />
                </div>
            </div>

            <div>
                <x-label value="{{ __('Motivo') }}" />
                <textarea
                    wire:model="motive"
                    rows="2"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="{{ __('Describa el motivo del viaje') }}"
                ></textarea>
                <x-input-error for="motive" class="mt-2" />
            </div>
        </form>
    </x-slot>

    <x-slot name="footer">
        <x-secondary-button wire:click="close" class="mr-2">
            {{ __('Cancelar') }}
        </x-secondary-button>
        <x-button wire:click="submit" wire:loading.attr="disabled">
            {{ __('Enviar Solicitud') }}
        </x-button>
    </x-slot>
</x-dialog-modal>