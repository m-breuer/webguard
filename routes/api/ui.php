<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Internal\Ui\AppearanceController;
use App\Http\Controllers\Api\Internal\Ui\DashboardController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringCardsController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringDetailDataController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringFormOptionsController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringIndexController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringManagementController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringOwnershipController;
use App\Http\Controllers\Api\Internal\Ui\MonitoringShowController;
use App\Http\Controllers\Api\Internal\Ui\PasswordController;
use App\Http\Controllers\Api\Internal\Ui\ProfileController;
use App\Http\Controllers\Api\Internal\Ui\SessionController;
use App\Http\Controllers\Api\Internal\Ui\TeamIndexController;
use App\Http\Controllers\Api\Internal\Ui\TeamStoreController;
use App\Http\Controllers\Api\Mobile\MobileMaintenanceController;
use App\Http\Controllers\Api\Mobile\MobileMonitoringGroupController;
use App\Http\Controllers\Api\Mobile\MobileMonitoringNotificationPreferenceController;
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
        Route::get('/monitoring-groups', [MobileMonitoringGroupController::class, 'index'])->name('monitoring-groups.index');
        Route::get('/monitoring-groups/assignment-options', [MobileMonitoringGroupController::class, 'assignmentOptions'])->name('monitoring-groups.assignment-options');
        Route::post('/monitoring-groups', [MobileMonitoringGroupController::class, 'store'])->name('monitoring-groups.store');
        Route::get('/monitoring-groups/{monitoringGroup}', [MobileMonitoringGroupController::class, 'show'])->name('monitoring-groups.show');
        Route::patch('/monitoring-groups/{monitoringGroup}', [MobileMonitoringGroupController::class, 'update'])->name('monitoring-groups.update');
        Route::delete('/monitoring-groups/{monitoringGroup}', [MobileMonitoringGroupController::class, 'destroy'])->name('monitoring-groups.destroy');
        Route::get('/maintenance/capabilities', [MobileMaintenanceController::class, 'capabilities'])->name('maintenance.capabilities');
        Route::get('/maintenance/one-off', [MobileMaintenanceController::class, 'oneOffIndex'])->name('maintenance.one-off.index');
        Route::get('/maintenance/recurring', [MobileMaintenanceController::class, 'recurringIndex'])->name('maintenance.recurring.index');
        Route::post('/maintenance', [MobileMaintenanceController::class, 'store'])->name('maintenance.store');
        Route::patch('/maintenance/recurring/{maintenanceWindow}', [MobileMaintenanceController::class, 'updateRecurring'])->name('maintenance.recurring.update');
        Route::delete('/maintenance/one-off/{monitoring}', [MobileMaintenanceController::class, 'cancelOneOff'])->name('maintenance.one-off.destroy');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/monitorings', MonitoringIndexController::class)->name('monitorings.index');
        Route::get('/monitorings/form-options', [MonitoringFormOptionsController::class, 'create'])->name('monitorings.form-options');
        Route::post('/monitorings', [MonitoringManagementController::class, 'store'])->name('monitorings.store');
        Route::get('/monitorings/cards', MonitoringCardsController::class)->name('monitorings.cards');
        Route::get('/monitorings/{monitoring}/form-options', [MonitoringFormOptionsController::class, 'edit'])->name('monitorings.form-options.edit');
        Route::get('/monitorings/{monitoring}/detail-data', MonitoringDetailDataController::class)->name('monitorings.detail-data');
        Route::get('/monitorings/{monitoring}/notification-preferences', [MobileMonitoringNotificationPreferenceController::class, 'show'])->name('monitorings.notification-preferences.show');
        Route::patch('/monitorings/{monitoring}/notification-preferences', [MobileMonitoringNotificationPreferenceController::class, 'update'])->name('monitorings.notification-preferences.update');
        Route::post('/monitorings/{monitoring}/ownership/team', [MonitoringOwnershipController::class, 'moveToTeam'])->name('monitorings.ownership.team.store');
        Route::post('/monitorings/{monitoring}/ownership/private', [MonitoringOwnershipController::class, 'moveToPrivate'])->name('monitorings.ownership.private.store');
        Route::patch('/monitorings/{monitoring}', [MonitoringManagementController::class, 'update'])->name('monitorings.update');
        Route::delete('/monitorings/{monitoring}', [MonitoringManagementController::class, 'destroy'])->name('monitorings.destroy');
        Route::get('/monitorings/{monitoring}', MonitoringShowController::class)->name('monitorings.show');
    });
});
