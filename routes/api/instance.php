<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Internal\MonitoringController;
use App\Http\Controllers\Api\Internal\MonitoringListController;
use Illuminate\Support\Facades\Route;

$registerInstanceRoutes = static function (string $prefix, string $namePrefix): void {
    Route::group(['prefix' => $prefix, 'as' => $namePrefix, 'middleware' => ['auth.instance']], function (): void {
        Route::get('monitorings', MonitoringListController::class)->name('monitorings.list');
        Route::post('monitoring-responses', [MonitoringController::class, 'storeResponse'])->name('monitoring-responses.store');
        Route::post('incidents', [MonitoringController::class, 'storeIncident'])->name('incidents.store');
        Route::put('incidents/{monitoring}', [MonitoringController::class, 'updateIncident'])->name('incidents.update');
        Route::post('ssl-results', [MonitoringController::class, 'storeSsl'])->name('ssl-results.store');
        Route::post('domain-results', [MonitoringController::class, 'storeDomain'])->name('domain-results.store');
    });
};

$registerInstanceRoutes('v1/internal', 'v1.internal.');
$registerInstanceRoutes('v1/internal/instances', 'v1.internal.instances.');
