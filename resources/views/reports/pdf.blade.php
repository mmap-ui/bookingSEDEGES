<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Flota</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 2px 0; }
        h2 { font-size: 13px; margin: 24px 0 8px 0; border-bottom: 1px solid #999; padding-bottom: 4px; }
        .meta { color: #666; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #bbb; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background-color: #f0f0f0; font-size: 10px; text-transform: uppercase; }
        .muted { color: #777; }
        .summary { width: auto; margin-top: 8px; }
        .summary td { border: none; padding: 2px 14px 2px 0; font-size: 12px; }
        .summary strong { font-size: 14px; }
    </style>
</head>
<body>
    <h1>Reporte de Uso de Flota</h1>
    <p class="meta">
        Periodo: {{ $start->format('d/m/Y') }} — {{ $end->format('d/m/Y') }}
        · Generado el {{ now()->format('d/m/Y H:i') }}
    </p>

    <table class="summary">
        <tr>
            <td>{{ __('Reservas en el rango') }}:<br><strong>{{ $totalReservations }}</strong></td>
            <td>{{ __('Horas totales reservadas') }}:<br><strong>{{ $totalHours }} h</strong></td>
            <td>{{ __('Vehículos con actividad') }}:<br><strong>{{ $usageRows->filter(fn ($row) => $row['occupations']->isNotEmpty())->count() }}</strong></td>
        </tr>
    </table>

    <h2>Uso por vehículo</h2>
    <table>
        <thead>
            <tr>
                <th style="width:22%">{{ __('Vehículo') }}</th>
                <th style="width:14%">{{ __('Chofer asignado') }}</th>
                <th style="width:12%">{{ __('Horas') }}</th>
                <th>{{ __('Reservas en el rango') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($usageRows as $row)
                <tr>
                    <td>
                        <strong>{{ $row['vehicle']->plate }}</strong><br>
                        <span class="muted">{{ $row['vehicle']->brand }} {{ $row['vehicle']->model }}</span>
                    </td>
                    <td>{{ $row['vehicle']->driver?->name ?? '—' }}</td>
                    <td>{{ round($row['reservedSeconds'] / 3600, 1) }} h</td>
                    <td>
                        @forelse ($row['occupations'] as $reservation)
                            {{ $reservation->start_at->format('d/m H:i') }} → {{ $reservation->end_at->format('d/m H:i') }}
                            · {{ $reservation->user->name }}
                            @if ($reservation->destination)
                                · {{ __('Traslado a') }} {{ $reservation->destination }}
                            @endif
                            <br>
                        @empty
                            <span class="muted">—</span>
                        @endforelse
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Mantenimientos activos ({{ $activeMaintenances->count() }})</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('Vehículo') }}</th>
                <th>{{ __('Inicio') }}</th>
                <th>{{ __('Fin estimado') }}</th>
                <th>{{ __('Chofer asignado') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($activeMaintenances as $vehicle)
                <tr>
                    <td>
                        <strong>{{ $vehicle->plate }}</strong><br>
                        <span class="muted">{{ $vehicle->brand }} {{ $vehicle->model }}</span>
                    </td>
                    <td>{{ $vehicle->maintenance_start_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>{{ $vehicle->maintenance_end_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>{{ $vehicle->driver?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Ningún vehículo en mantenimiento actualmente.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
