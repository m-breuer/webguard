<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Internal\Ui\DashboardController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringCardsController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringIndexController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringShowController;
use App\Http\Middleware\MeasureInternalUiRequest;
use Illuminate\Support\Facades\Route;

Route::middleware(MeasureInternalUiRequest::class)->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/monitorings', MonitoringIndexController::class)->name('monitorings.index');
    Route::get('/monitorings/cards', MonitoringCardsController::class)->name('monitorings.cards');
    Route::get('/monitorings/{monitoring}', MonitoringShowController::class)->name('monitorings.show');
});
