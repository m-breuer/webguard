<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Internal\Ui\AppearanceController;
use App\Http\Controllers\Api\Internal\Ui\DashboardController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringCardsController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringIndexController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringShowController;
use App\Http\Controllers\Api\Internal\Ui\ProfileController;
use App\Http\Controllers\Api\Internal\Ui\SessionController;
use App\Http\Middleware\MeasureInternalUiRequest;
use Illuminate\Support\Facades\Route;

Route::middleware(MeasureInternalUiRequest::class)->group(function (): void {
    Route::get('/session', [SessionController::class, 'show'])->name('session.show');
    Route::post('/session/logout', [SessionController::class, 'destroy'])->name('session.destroy');
    Route::patch('/appearance', AppearanceController::class)
        ->middleware('role:member,admin')
        ->name('appearance.update');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->middleware('role:member,admin')
        ->name('profile.update');

    Route::middleware('verified')->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/monitorings', MonitoringIndexController::class)->name('monitorings.index');
        Route::get('/monitorings/cards', MonitoringCardsController::class)->name('monitorings.cards');
        Route::get('/monitorings/{monitoring}', MonitoringShowController::class)->name('monitorings.show');
    });
});
