<?php

declare(strict_types=1);

use App\Http\Controllers\Api\MonitoringManagementController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TeamInvitationController;
use App\Http\Controllers\Api\TeamMemberController;
use App\Http\Controllers\ApiController;
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

    Route::get('/{monitoring}', [ApiController::class, 'all']);

    Route::get('/{monitoring}/status', [ApiController::class, 'status']);
    Route::get('/{monitoring}/uptime-downtime', [ApiController::class, 'uptimeDowntime']);
    Route::get('/{monitoring}/uptime-downtime-summary', [ApiController::class, 'uptimeDowntimeSummary']);
    Route::get('/{monitoring}/response-times', [ApiController::class, 'responseTimes']);
    Route::get('/{monitoring}/checks', [ApiController::class, 'checks']);
    Route::get('/{monitoring}/incidents', [ApiController::class, 'incidents']);
    Route::get('/{monitoring}/heatmap', [ApiController::class, 'uptimeHeatmap']);
    Route::get('/{monitoring}/ssl', [ApiController::class, 'sslStatus']);
    Route::get('/{monitoring}/uptime-calendar', [ApiController::class, 'uptimeCalendar']);
});

Route::apiResource('teams', TeamController::class);
Route::get('/teams/{team}/members', [TeamMemberController::class, 'index']);
Route::patch('/teams/{team}/members/{teamMembership}', [TeamMemberController::class, 'update']);
Route::delete('/teams/{team}/members/{teamMembership}', [TeamMemberController::class, 'destroy']);
Route::get('/teams/{team}/invitations', [TeamInvitationController::class, 'index']);
Route::post('/teams/{team}/invitations', [TeamInvitationController::class, 'store']);
Route::delete('/teams/{team}/invitations/{teamInvitation}', [TeamInvitationController::class, 'destroy']);
Route::post('/team-invitations/{token}/accept', [TeamInvitationController::class, 'accept']);
