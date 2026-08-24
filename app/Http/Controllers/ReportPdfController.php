<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class ReportPdfController extends Controller
{
    /**
     * Exports the usage report and active maintenances as a PDF download.
     */
    public function __invoke(Request $request): Response
    {
        $start = filled($request->query('start')) ? Carbon::parse($request->query('start'))->startOfDay() : null;
        $end = filled($request->query('end')) ? Carbon::parse($request->query('end'))->endOfDay() : null;
        $vehicleId = $request->filled('vehicle') ? $request->integer('vehicle') : null;
        $driverId = $request->filled('driver') ? $request->integer('driver') : null;

        $hasRange = $start !== null && $end !== null && $start->lessThanOrEqualTo($end);

        if (! $hasRange) {
            abort(422, 'Rango de fechas no válido.');
        }

        $report = app(ReportService::class);
        $usageRows = collect($report->usageRows($start, $end, $vehicleId, $driverId));

        /** @var Collection $maintenances */
        $maintenances = $report->activeMaintenances();

        $totalReservations = $usageRows->sum(fn (array $row) => $row['occupations']->count());
        $totalHours = round(
            $usageRows->sum(fn (array $row) => $row['reservedSeconds']) / 3600,
            1
        );

        $pdf = Pdf::loadView('reports.pdf', [
            'usageRows' => $usageRows,
            'activeMaintenances' => $maintenances,
            'start' => $start,
            'end' => $end,
            'totalReservations' => $totalReservations,
            'totalHours' => $totalHours,
        ]);

        return $pdf->download("reporte-flota-{$start->format('Ymd')}-{$end->format('Ymd')}.pdf");
    }
}
