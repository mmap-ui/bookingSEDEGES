<div class="space-y-6">
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

    <div class="grid gap-4 sm:grid-cols-2 lg:max-w-4xl">
        <div>
            <x-label for="calendar-vehicle-filter" value="{{ __('Vehículo') }}" />
            <select
                id="calendar-vehicle-filter"
                wire:model.live="vehicleId"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">{{ __('Todos los vehículos') }}</option>
                @foreach ($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}">{{ $vehicle->plate }} · {{ $vehicle->brand }} {{ $vehicle->model }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <x-label for="calendar-status-filter" value="{{ __('Estado') }}" />
            <select
                id="calendar-status-filter"
                wire:model.live="status"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="todas">{{ __('Aprobadas y pendientes') }}</option>
                <option value="aprobada">{{ __('Solo aprobadas') }}</option>
                <option value="pendiente">{{ __('Solo pendientes') }}</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden p-4">
        <div wire:ignore>
            <div x-data="calendarApp({ endpoint: @js(route('calendario.eventos')), canCreate: @js(auth()->user()->can('crear reservas')), authUserId: @js(auth()->id()), isApprover: @js(auth()->user()->hasPermissionTo('aprobar reservas')) })">
                <div x-ref="calendar"></div>

                <div x-show="selectedEvent" x-cloak x-transition.opacity.duration.200ms class="mt-4">
                    <div class="rounded-md border border-gray-200 bg-gray-50 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-gray-900" x-text="selectedEvent ? selectedEvent.vehicle : ''"></p>
                                <p class="mt-1 text-xs text-gray-600" x-text="selectedEvent ? selectedEvent.motive : ''"></p>
                                <p class="mt-1 text-xs text-gray-600">
                                    <span x-text="selectedEvent ? selectedEvent.requester : ''"></span>
                                    <span x-show="selectedEvent && selectedEvent.destination" x-text="' · ' + selectedEvent.destination"></span>
                                </p>
                                <p class="mt-1 text-xs text-gray-600" x-text="selectedEvent ? selectedEvent.statusLabel : ''"></p>
                            </div>
                            <button
                                type="button"
                                @click="closeDetails()"
                                class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs text-gray-600 hover:bg-gray-100"
                            >
                                {{ __('Cerrar') }}
                            </button>
                        </div>

                        <template x-if="selectedEvent && selectedEvent.status === 'pendiente' && selectedEvent.conflicts && selectedEvent.conflicts.length > 0">
                            <div class="mt-3 rounded-md border border-orange-300 bg-orange-50 p-3">
                                <p class="flex items-center gap-1.5 text-xs font-semibold text-orange-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 6a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                                    </svg>
                                    {{ __('Horarios superpuestos') }} (<span x-text="selectedEvent.conflicts.length"></span>)
                                </p>
                                <p class="mt-1 text-xs text-orange-700">{{ __('Existen otras solicitudes pendientes para este vehículo en el mismo horario. Revísalas y decide cuál tiene prioridad:') }}</p>
                                <template x-for="(conflict, index) in selectedEvent.conflicts" :key="index">
                                    <div class="mt-2 rounded border border-orange-200 bg-white px-3 py-2 text-xs text-gray-700">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div>
                                                <span class="font-semibold" x-text="conflict.requester"></span>
                                                · <span x-text="conflict.start"></span> → <span x-text="conflict.end"></span>
                                                <br>
                                                <span class="text-gray-500" x-text="'{{ __('Motivo:') }} ' + conflict.motive"></span>
                                            </div>
                                            @if (auth()->user()->hasPermissionTo('aprobar reservas'))
                                                <button
                                                    type="button"
                                                    @click="requestApprove(conflict.id)"
                                                    wire:loading.attr="disabled"
                                                    class="shrink-0 inline-flex items-center rounded bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-emerald-700"
                                                >
                                                    {{ __('Aprobar esta') }}
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <div
                            x-show="selectedEvent && (selectedEvent.requesterId === authUserId || isApprover)"
                            class="mt-4 border-t border-gray-200 pt-4"
                        >
                            <template x-if="isApprover && selectedEvent && selectedEvent.status === 'pendiente'">
                                <div class="mb-4">
                                    <template x-if="!confirmingApprove">
                                        <button
                                            type="button"
                                            @click="confirmingApprove = true"
                                            class="inline-flex items-center rounded border border-emerald-600 bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700"
                                        >
                                            {{ __('Aprobar reserva') }}
                                        </button>
                                    </template>

                                    <template x-if="confirmingApprove">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <span class="text-sm text-emerald-700">{{ __('¿Aprobar esta reserva?') }}</span>
                                            <button
                                                type="button"
                                                @click="requestApprove()"
                                                class="inline-flex items-center rounded border border-emerald-600 bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700"
                                            >
                                                {{ __('Sí, aprobar') }}
                                            </button>
                                            <button
                                                type="button"
                                                @click="confirmingApprove = false"
                                                class="inline-flex items-center rounded border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-100"
                                            >
                                                {{ __('Volver') }}
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!confirmingCancel">
                                <button
                                    type="button"
                                    @click="confirmingCancel = true"
                                    class="inline-flex items-center rounded border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50"
                                >
                                    {{ __('Cancelar reserva') }}
                                </button>
                            </template>

                            <template x-if="confirmingCancel">
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="text-sm text-red-700">{{ __('¿Cancelar esta reserva?') }}</span>
                                    <button
                                        type="button"
                                        @click="requestCancel()"
                                        class="inline-flex items-center rounded border border-red-600 bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700"
                                    >
                                        {{ __('Sí, cancelar') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="confirmingCancel = false"
                                        class="inline-flex items-center rounded border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-100"
                                    >
                                        {{ __('Volver') }}
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>