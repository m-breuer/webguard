<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Internal\Ui\AppearanceController;
use App\Http\Controllers\Api\Internal\Ui\DashboardController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringCardsController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringDetailDataController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringFormOptionsController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringIndexController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringManagementController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringShowController;
use App\Http\Controllers\Api\Internal\Ui\PasswordController;
use App\Http\Controllers\Api\Internal\Ui\ProfileController;
use App\Http\Controllers\Api\Internal\Ui\SessionController;
use App\Http\Controllers\Api\Internal\Ui\TeamIndexController;
use App\Http\Controllers\Api\Internal\Ui\TeamStoreController;
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
    Route::put('/profile/password', [PasswordController::class, 'update'])
        ->middleware('role:member,admin')
        ->name('profile.password.update');

    Route::middleware('verified')->group(function (): void {
        Route::get('/teams', TeamIndexController::class)->name('teams.index');
        Route::post('/teams', TeamStoreController::class)->name('teams.store');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/monitorings', MonitoringIndexController::class)->name('monitorings.index');
        Route::get('/monitorings/form-options', [MonitoringFormOptionsController::class, 'create'])->name('monitorings.form-options');
        Route::post('/monitorings', [MonitoringManagementController::class, 'store'])->name('monitorings.store');
        Route::get('/monitorings/cards', MonitoringCardsController::class)->name('monitorings.cards');
        Route::get('/monitorings/{monitoring}/form-options', [MonitoringFormOptionsController::class, 'edit'])->name('monitorings.form-options.edit');
        Route::get('/monitorings/{monitoring}/detail-data', MonitoringDetailDataController::class)->name('monitorings.detail-data');
        Route::patch('/monitorings/{monitoring}', [MonitoringManagementController::class, 'update'])->name('monitorings.update');
        Route::delete('/monitorings/{monitoring}', [MonitoringManagementController::class, 'destroy'])->name('monitorings.destroy');
        Route::get('/monitorings/{monitoring}', MonitoringShowController::class)->name('monitorings.show');
    });
});
