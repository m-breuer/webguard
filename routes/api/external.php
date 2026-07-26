<?php

declare(strict_types=1);

use App\Http\Controllers\Api\External\MobileOverviewController;
use App\Http\Controllers\Api\External\MobilePushDeviceController;
use App\Http\Controllers\Api\External\MonitoringDataController;
use App\Http\Controllers\Api\External\MonitoringManagementController;
use App\Http\Controllers\Api\External\TeamController;
use App\Http\Controllers\Api\External\TeamInvitationController;
use App\Http\Controllers\Api\External\TeamMemberController;
use Illuminate\Support\Facades\Route;

/**
 * The routes are sorted in a logical order.
 * The all route is first, then the status routes, then the data routes.
 * This is to ensure that the documentation is generated in a logical order.
 */
Route::group(['prefix' => 'monitorings', 'as' => 'monitorings.'], function (): void {
    Route::get('/', [MonitoringManagementController::class, 'index']);
    Route::post('/', [MonitoringManagementController::class, 'store']);
    Route::patch('/{monitoring}', [MonitoringManagementController::class, 'update']);
    Route::delete('/{monitoring}', [MonitoringManagementController::class, 'destroy']);
    Route::post('/{monitoring}/team-ownership', [MonitoringManagementController::class, 'moveToTeam']);
    Route::delete('/{monitoring}/team-ownership', [MonitoringManagementController::class, 'moveToPrivate']);

    Route::get('/{monitoring}', [MonitoringDataController::class, 'all']);

    Route::get('/{monitoring}/status', [MonitoringDataController::class, 'status']);
    Route::get('/{monitoring}/uptime-downtime', [MonitoringDataController::class, 'uptimeDowntime']);
    Route::get('/{monitoring}/uptime-downtime-summary', [MonitoringDataController::class, 'uptimeDowntimeSummary']);
    Route::get('/{monitoring}/response-times', [MonitoringDataController::class, 'responseTimes']);
    Route::get('/{monitoring}/checks', [MonitoringDataController::class, 'checks']);
    Route::get('/{monitoring}/incidents', [MonitoringDataController::class, 'incidents']);
    Route::get('/{monitoring}/heatmap', [MonitoringDataController::class, 'uptimeHeatmap']);
    Route::get('/{monitoring}/ssl', [MonitoringDataController::class, 'sslStatus']);
    Route::get('/{monitoring}/uptime-calendar', [MonitoringDataController::class, 'uptimeCalendar']);
});

Route::get('/mobile/overview', MobileOverviewController::class)
    ->name('mobile.overview');

Route::apiResource('mobile-push-devices', MobilePushDeviceController::class)
    ->only(['index', 'store', 'update', 'destroy']);

Route::apiResource('teams', TeamController::class);
Route::get('/teams/{team}/members', [TeamMemberController::class, 'index']);
Route::patch('/teams/{team}/members/{teamMembership}', [TeamMemberController::class, 'update']);
Route::delete('/teams/{team}/members/{teamMembership}', [TeamMemberController::class, 'destroy']);
Route::get('/teams/{team}/invitations', [TeamInvitationController::class, 'index']);
Route::post('/teams/{team}/invitations', [TeamInvitationController::class, 'store']);
Route::delete('/teams/{team}/invitations/{teamInvitation}', [TeamInvitationController::class, 'destroy']);
Route::post('/team-invitations/{token}/accept', [TeamInvitationController::class, 'accept']);
