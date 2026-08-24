<div class="space-y-6">
    @if ($adminStats !== null && $pendingApprovalsCount > 0)
        <a href="{{ route('solicitudes') }}" class="block rounded-lg border border-amber-300 bg-amber-50 p-4 hover:bg-amber-100 transition">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 text-amber-600 shrink-0">
                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z" clip-rule="evenodd" />
                </svg>
                <p class="text-sm text-amber-900">
                    Tienes <span class="font-bold">{{ $pendingApprovalsCount }}</span>
                    {{ $pendingApprovalsCount === 1 ? 'solicitud pendiente de aprobación.' : 'solicitudes pendientes de aprobación.' }}
                    <span class="underline font-medium">Ir a solicitudes →</span>
                </p>
            </div>
        </a>
    @endif

    @if ($inProgressNow->isNotEmpty())
        <div class="bg-white rounded-lg shadow overflow-hidden border border-emerald-200">
            <div class="p-6 pb-4 flex items-center gap-2">
                <span class="relative flex size-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full size-2.5 bg-emerald-500"></span>
                </span>
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">
                    {{ __('En curso ahora') }} ({{ $inProgressNow->count() }})
                </h3>
            </div>
            <ul class="divide-y divide-gray-100 px-6 pb-6 pt-0 space-y-0">
                @foreach ($inProgressNow as $reservation)
                    <li class="py-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ $reservation->vehicle->plate }}
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 ms-1">En curso</span>
                                </p>
                                <p class="text-xs text-gray-600">
                                    {{ __('Traslado a') }} {{ $reservation->destination ?? $reservation->motive }}
                                    · {{ $reservation->user->name }}
                                </p>
                            </div>
                            <p class="text-xs font-medium text-emerald-700">
                                {{ __('Retorna') }}: {{ $reservation->end_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-900 uppercase tracking-wide">
                <span class="inline-block size-2 rounded-full bg-emerald-500"></span>
                {{ __('Vehículos programados hoy') }} ({{ $scheduledToday->count() }})
            </h3>

            @if ($scheduledToday->isEmpty())
                <p class="mt-4 text-sm text-gray-500">{{ __('Ningún vehículo está programado para hoy.') }}</p>
            @else
                <ul class="mt-4 space-y-3">
                    @foreach ($scheduledToday as $reservation)
                        @php
                            $inProgress = $reservation->start_at <= now() && $reservation->end_at >= now();
                        @endphp
                        <li class="rounded-md border border-gray-200 px-4 py-3 {{ $inProgress ? 'border-emerald-300 bg-emerald-50' : '' }}">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ $reservation->vehicle->plate }}
                                    <span class="font-normal text-gray-500">· {{ $reservation->vehicle->brand }} {{ $reservation->vehicle->model }}</span>
                                </p>
                                @if ($inProgress)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">{{ __('En curso') }}</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-600">
                                {{ $reservation->start_at->format('H:i') }} → {{ $reservation->end_at->format('H:i') }}
                                · {{ $reservation->user->name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $reservation->destination ? __('Traslado a') . ' ' . $reservation->destination : $reservation->motive }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-900 uppercase tracking-wide">
                    <span class="inline-block size-2 rounded-full bg-sky-500"></span>
                    {{ __('Vehículos libres hoy') }} ({{ $freeVehicles->count() }})
                </h3>

                @if ($freeVehicles->isEmpty())
                    <p class="mt-4 text-sm text-gray-500">{{ __('No hay vehículos libres para hoy.') }}</p>
                @else
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($freeVehicles as $vehicle)
                            <span class="inline-flex items-center rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1 text-xs font-medium text-emerald-800">
                                {{ $vehicle->plate }} · {{ $vehicle->brand }} {{ $vehicle->model }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($maintenanceVehicles->isNotEmpty())
                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-900 uppercase tracking-wide">
                        <span class="inline-block size-2 rounded-full bg-amber-500"></span>
                        {{ __('En mantenimiento') }} ({{ $maintenanceVehicles->count() }})
                    </h3>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($maintenanceVehicles as $vehicle)
                            <span class="inline-flex items-center rounded-full bg-amber-50 border border-amber-200 px-3 py-1 text-xs font-medium text-amber-800">
                                {{ $vehicle->plate }} · {{ $vehicle->brand }} {{ $vehicle->model }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($driverStats !== null)
        <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">{{ __('Mis solicitudes') }}</h3>
            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-amber-800">{{ $driverStats['pendiente'] }}</p>
                    <p class="text-xs text-amber-700">{{ __('Pendientes') }}</p>
                </div>
                <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-emerald-800">{{ $driverStats['aprobada'] }}</p>
                    <p class="text-xs text-emerald-700">{{ __('Aceptadas') }}</p>
                </div>
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-red-800">{{ $driverStats['rechazada'] }}</p>
                    <p class="text-xs text-red-700">{{ __('Rechazadas') }}</p>
                </div>
                <div class="rounded-md bg-gray-100 border border-gray-200 px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-gray-700">{{ $driverStats['cancelada'] }}</p>
                    <p class="text-xs text-gray-600">{{ __('Canceladas') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if ($upcomingThisWeek->isNotEmpty())
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 pb-4">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">{{ __('Próximas salidas (próximos 7 días)') }}</h3>
            </div>
            <ul class="divide-y divide-gray-100 px-6 pb-6 pt-0">
                @foreach ($upcomingThisWeek as $reservation)
                    <li class="py-3 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">
                                {{ $reservation->vehicle->plate }}
                                <span class="font-normal text-gray-500">· {{ $reservation->user->name }}</span>
                            </p>
                            <p class="text-xs text-gray-600">
                                {{ $reservation->destination ? __('Traslado a') . ' ' . $reservation->destination : $reservation->motive }}
                            </p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-sky-50 border border-sky-200 px-3 py-1 text-xs font-medium text-sky-800">
                            {{ $reservation->start_at->format('d/m/Y H:i') }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($adminStats !== null)
        <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">{{ __('Resumen de solicitudes') }}</h3>
            <div class="mt-4 grid grid-cols-3 gap-4">
                <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-amber-800">{{ $adminStats['pendiente'] }}</p>
                    <p class="text-xs text-amber-700">{{ __('Pendientes') }}</p>
                </div>
                <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-emerald-800">{{ $adminStats['aprobada'] }}</p>
                    <p class="text-xs text-emerald-700">{{ __('Aprobadas') }}</p>
                </div>
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-red-800">{{ $adminStats['rechazada'] }}</p>
                    <p class="text-xs text-red-700">{{ __('Rechazadas') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 pb-4">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">{{ __('Frecuencia de uso por vehículo (últimos 30 días)') }}</h3>
                <p class="mt-1 text-xs text-gray-500">{{ __('Vehículos con más viajes aprobados; útil para planificar mantenimientos.') }}</p>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Vehículo') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Estado') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Viajes aprobados (30 días)') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($vehicleUsage as $vehicle)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $vehicle->plate }}
                                <span class="font-normal text-gray-500">· {{ $vehicle->brand }} {{ $vehicle->model }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if ($vehicle->status->isMaintenance())
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">{{ $vehicle->status->label() }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">{{ $vehicle->status->label() }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-end">
                                {{ $vehicle->approved_reservations_30d }}
                                @if ($vehicle->approved_reservations_30d >= $highUsageThreshold)
                                    <span class="ms-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                                        {{ __('Sugerir revisión') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
