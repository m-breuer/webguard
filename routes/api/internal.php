<?php

declare(strict_types=1);

use App\Http\Controllers\Api\MaintenanceDataController;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::get('/maintenance', MaintenanceDataController::class)->name('maintenance.index');

Route::group(['prefix' => 'monitorings/{monitoring}', 'as' => 'monitorings.analytics.'], function (): void {
    Route::get('/analytics', [ApiController::class, 'all'])->name('all');
    Route::get('/status', [ApiController::class, 'status'])->name('status');
    Route::get('/uptime-downtime', [ApiController::class, 'uptimeDowntime'])->name('uptime-downtime');
    Route::get('/uptime-downtime-summary', [ApiController::class, 'uptimeDowntimeSummary'])->name('uptime-downtime-summary');
    Route::get('/response-times', [ApiController::class, 'responseTimes'])->name('response-times');
    Route::get('/server-health-telemetry', [ApiController::class, 'serverHealthTelemetry'])->name('server-health-telemetry');
    Route::get('/checks', [ApiController::class, 'checks'])->name('checks');
    Route::get('/incidents', [ApiController::class, 'incidents'])->name('incidents');
    Route::get('/heatmap', [ApiController::class, 'uptimeHeatmap'])->name('heatmap');
    Route::get('/ssl', [ApiController::class, 'sslStatus'])->name('ssl');
    Route::get('/uptime-calendar', [ApiController::class, 'uptimeCalendar'])->name('uptime-calendar');
});
