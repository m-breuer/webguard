<?php

declare(strict_types=1);

use App\Http\Controllers\Api\MonitoringCardDataController;
use App\Http\Controllers\Api\NotificationBoardController;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::get('/notifications/status-board', NotificationBoardController::class)->name('notifications.status-board');

Route::group(['prefix' => 'monitorings', 'as' => 'monitorings.'], function (): void {
    Route::get('/card-data', MonitoringCardDataController::class)->name('card-data');
    Route::get('/{monitoring}', [ApiController::class, 'all']);

    Route::get('/{monitoring}/status', [ApiController::class, 'status']);
    Route::get('/{monitoring}/uptime-downtime', [ApiController::class, 'uptimeDowntime']);
    Route::get('/{monitoring}/response-times', [ApiController::class, 'responseTimes']);
    Route::get('/{monitoring}/checks', [ApiController::class, 'checks']);
    Route::get('/{monitoring}/incidents', [ApiController::class, 'incidents']);
    Route::get('/{monitoring}/custom-range-stats', [ApiController::class, 'customRangeStats']);
    Route::get('/{monitoring}/heatmap', [ApiController::class, 'uptimeHeatmap']);
    Route::get('/{monitoring}/ssl', [ApiController::class, 'sslStatus']);
    Route::get('/{monitoring}/uptime-calendar', [ApiController::class, 'uptimeCalendar']);
});
