<?php

declare(strict_types=1);

use App\Http\Controllers\Api\MaintenanceDataController;
use App\Http\Controllers\Api\NotificationBoardController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\MaintenanceController as WebMaintenanceController;
use Illuminate\Support\Facades\Route;

Route::get('/notifications/status-board', NotificationBoardController::class)->name('notifications.status-board');

Route::get('/maintenance', MaintenanceDataController::class)->name('maintenance.index');
Route::post('/maintenance', [WebMaintenanceController::class, 'store'])->name('maintenance.store');
Route::delete('/maintenance', [WebMaintenanceController::class, 'destroy'])->name('maintenance.destroy');

Route::group(['prefix' => 'monitorings', 'as' => 'monitorings.'], function (): void {
    Route::get('/{monitoring}', [ApiController::class, 'all']);

    Route::get('/{monitoring}/status', [ApiController::class, 'status']);
    Route::get('/{monitoring}/uptime-downtime', [ApiController::class, 'uptimeDowntime']);
    Route::get('/{monitoring}/uptime-downtime-summary', [ApiController::class, 'uptimeDowntimeSummary']);
    Route::get('/{monitoring}/response-times', [ApiController::class, 'responseTimes']);
    Route::get('/{monitoring}/server-health-telemetry', [ApiController::class, 'serverHealthTelemetry']);
    Route::get('/{monitoring}/checks', [ApiController::class, 'checks']);
    Route::get('/{monitoring}/incidents', [ApiController::class, 'incidents']);
    Route::get('/{monitoring}/heatmap', [ApiController::class, 'uptimeHeatmap']);
    Route::get('/{monitoring}/ssl', [ApiController::class, 'sslStatus']);
    Route::get('/{monitoring}/uptime-calendar', [ApiController::class, 'uptimeCalendar']);
});
