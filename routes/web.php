<?php

use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\ReportPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/calendario', fn () => view('pages.calendar'))
        ->middleware('permission:ver reservas')
        ->name('calendario');

    Route::get('/calendario/eventos', CalendarEventController::class)
        ->middleware('permission:ver reservas')
        ->name('calendario.eventos');

    Route::get('/solicitudes', fn () => view('pages.solicitudes'))
        ->middleware('permission:aprobar reservas')
        ->name('solicitudes');

    Route::get('/vehiculos', fn () => view('pages.vehiculos'))
        ->middleware('permission:ver vehiculos')
        ->name('vehiculos');

    Route::get('/reportes', fn () => view('pages.reportes'))
        ->middleware('permission:ver reportes')
        ->name('reportes');

    Route::get('/reportes/pdf', ReportPdfController::class)
        ->middleware('permission:ver reportes')
        ->name('reportes.pdf');

    Route::get('/usuarios', fn () => view('pages.usuarios'))
        ->middleware('permission:gestionar usuarios')
        ->name('usuarios');

    Route::get('/roles', fn () => view('pages.roles'))
        ->middleware('permission:gestionar roles')
        ->name('roles');
});
