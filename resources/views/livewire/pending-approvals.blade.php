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

    <div class="bg-white rounded-lg shadow overflow-hidden">
        @forelse ($pendingReservations as $reservation)
            <div class="p-6 border-b border-gray-200 last:border-b-0">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                {{ __('Pendiente') }}
                            </span>
                            <p class="text-sm font-semibold text-gray-900">
                                {{ $reservation->vehicle->plate }}
                                <span class="font-normal text-gray-500">
                                    · {{ $reservation->vehicle->brand }} {{ $reservation->vehicle->model }}
                                </span>
                            </p>
                        </div>
                        <p class="text-sm text-gray-700">
                            <span class="font-medium">{{ __('Solicitante:') }}</span>
                            {{ $reservation->user->name }}
                            <span class="text-gray-400">({{ $reservation->user->email }})</span>
                        </p>
                        <p class="text-sm text-gray-700">
                            <span class="font-medium">{{ __('Cuándo:') }}</span>
                            {{ $reservation->start_at->format('d/m/Y H:i') }} → {{ $reservation->end_at->format('d/m/Y H:i') }}
                        </p>
                        <p class="text-sm text-gray-700">
                            <span class="font-medium">{{ __('Motivo:') }}</span>
                            {{ $reservation->motive }}
                        </p>
                        @if ($reservation->destination)
                            <p class="text-sm text-gray-700">
                                <span class="font-medium">{{ __('Destino:') }}</span>
                                {{ $reservation->destination }}
                            </p>
                        @endif

                        @if (isset($pendingConflicts[$reservation->id]))
                            <div class="mt-3 rounded-md border border-orange-300 bg-orange-50 p-3">
                                <p class="flex items-center gap-1.5 text-xs font-semibold text-orange-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 6a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                                    </svg>
                                    {{ __('Horarios superpuestos') }} ({{ count($pendingConflicts[$reservation->id]) }})
                                </p>
                                <p class="mt-1 text-xs text-orange-700">{{ __('Existen otras solicitudes pendientes para este vehículo en el mismo horario. Revísalas y decide cuál tiene prioridad:') }}</p>
                                <ul class="mt-2 space-y-2">
                                    @foreach ($pendingConflicts[$reservation->id] as $conflict)
                                        <li class="rounded border border-orange-200 bg-white px-3 py-2 text-xs text-gray-700">
                                            <span class="font-semibold">{{ $conflict->user->name }}</span>
                                            · {{ $conflict->start_at->format('d/m/Y H:i') }} → {{ $conflict->end_at->format('d/m/Y H:i') }}
                                            <br>
                                            <span class="text-gray-500">{{ __('Motivo:') }} {{ $conflict->motive }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    @if ($rejectingId === $reservation->id)
                        <div class="w-full lg:w-80 space-y-2">
                            <textarea
                                wire:model="rejectionReason"
                                rows="2"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="{{ __('Motivo del rechazo (opcional)') }}"
                            ></textarea>
                            <x-input-error for="rejectionReason" class="mt-2" />
                            <div class="flex gap-2">
                                <x-button wire:click="confirmReject">Rechazar</x-button>
                                <x-secondary-button wire:click="cancelRejection">Cancelar</x-secondary-button>
                            </div>
                        </div>
                    @else
                        <div class="flex gap-2 shrink-0">
                            <x-button wire:click="approve({{ $reservation->id }})" wire:loading.attr="disabled">
                                {{ __('Aprobar') }}
                            </x-button>
                            <x-secondary-button wire:click="startRejection({{ $reservation->id }})">
                                {{ __('Rechazar') }}
                            </x-secondary-button>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-12 text-center text-gray-500">
                {{ __('No hay solicitudes pendientes.') }}
            </div>
        @endforelse
    </div>

    @if ($pendingReservations->hasPages())
        <div class="mt-6">
            {{ $pendingReservations->links() }}
        </div>
    @endif
</div>