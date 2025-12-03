<?php

use App\Http\Controllers\Api\Internal\MonitoringController;
use App\Http\Controllers\Api\Internal\MonitoringListController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/internal', 'as' => 'v1.internal.', 'middleware' => ['auth.instance']], function (): void {
    Route::get('monitorings', MonitoringListController::class)->name('monitorings.list');
    Route::post('monitoring-responses', [MonitoringController::class, 'storeResponse'])->name('monitoring-responses.store');
    Route::post('incidents', [MonitoringController::class, 'storeIncident'])->name('incidents.store');
    Route::put('incidents/{monitoring}', [MonitoringController::class, 'updateIncident'])->name('incidents.update');
    Route::post('ssl-results', [MonitoringController::class, 'storeSsl'])->name('ssl-results.store');
});
