<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

/**
 * The routes are sorted in a logical order.
 * The all route is first, then the status routes, then the data routes.
 * This is to ensure that the documentation is generated in a logical order.
 */
Route::group(['prefix' => 'monitorings', 'as' => 'monitorings.'], function (): void {
    Route::get('/{monitoring}', [ApiController::class, 'all']);

    Route::get('/{monitoring}/status', [ApiController::class, 'status']);
    Route::get('/{monitoring}/uptime-downtime', [ApiController::class, 'uptimeDowntime']);
    Route::get('/{monitoring}/response-times', [ApiController::class, 'responseTimes']);
    Route::get('/{monitoring}/incidents', [ApiController::class, 'incidents']);
    Route::get('/{monitoring}/heatmap', [ApiController::class, 'uptimeHeatmap']);
    Route::get('/{monitoring}/ssl', [ApiController::class, 'sslStatus']);
    Route::get('/{monitoring}/uptime-calendar', [ApiController::class, 'uptimeCalendar']);
});
