<?php

declare(strict_types=1);

use App\Http\Controllers\Api\External\MobileMonitoringGroupController;
use App\Http\Controllers\Api\External\MobileMaintenanceController;
use App\Http\Controllers\Api\External\MobileOverviewController;
use App\Http\Controllers\Api\External\MobilePushDeviceController;
use App\Http\Controllers\Api\External\MobileStatusPageWorkspaceController;
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

    Route::get('/{monitoring}', [MonitoringDataController::class, 'all'])->name('analytics.all');

    Route::get('/{monitoring}/status', [MonitoringDataController::class, 'status'])->name('analytics.status');
    Route::get('/{monitoring}/uptime-downtime', [MonitoringDataController::class, 'uptimeDowntime'])->name('analytics.uptime-downtime');
    Route::get('/{monitoring}/uptime-downtime-summary', [MonitoringDataController::class, 'uptimeDowntimeSummary'])->name('analytics.uptime-downtime-summary');
    Route::get('/{monitoring}/response-times', [MonitoringDataController::class, 'responseTimes'])->name('analytics.response-times');
    Route::get('/{monitoring}/checks', [MonitoringDataController::class, 'checks'])->name('analytics.checks');
    Route::get('/{monitoring}/incidents', [MonitoringDataController::class, 'incidents'])->name('analytics.incidents');
    Route::get('/{monitoring}/heatmap', [MonitoringDataController::class, 'uptimeHeatmap'])->name('analytics.heatmap');
    Route::get('/{monitoring}/ssl', [MonitoringDataController::class, 'sslStatus'])->name('analytics.ssl');
    Route::get('/{monitoring}/uptime-calendar', [MonitoringDataController::class, 'uptimeCalendar'])->name('analytics.uptime-calendar');
});

Route::get('/mobile/overview', MobileOverviewController::class)
    ->name('mobile.overview');

Route::group(['prefix' => 'mobile/maintenance', 'as' => 'mobile.maintenance.'], function (): void {
    Route::get('/capabilities', [MobileMaintenanceController::class, 'capabilities'])->name('capabilities');
    Route::get('/one-off', [MobileMaintenanceController::class, 'oneOffIndex'])->name('one-off.index');
    Route::get('/recurring', [MobileMaintenanceController::class, 'recurringIndex'])->name('recurring.index');
    Route::post('/', [MobileMaintenanceController::class, 'store'])->name('store');
    Route::patch('/recurring/{maintenanceWindow}', [MobileMaintenanceController::class, 'updateRecurring'])->name('recurring.update');
    Route::delete('/one-off/{monitoring}', [MobileMaintenanceController::class, 'cancelOneOff'])->name('one-off.destroy');
});

Route::group(['prefix' => 'mobile/monitoring-groups', 'as' => 'mobile.monitoring-groups.'], function (): void {
    Route::get('/', [MobileMonitoringGroupController::class, 'index'])->name('index');
    Route::get('/assignment-options', [MobileMonitoringGroupController::class, 'assignmentOptions'])->name('assignment-options');
    Route::post('/', [MobileMonitoringGroupController::class, 'store'])->name('store');
    Route::get('/{monitoringGroup}', [MobileMonitoringGroupController::class, 'show'])->name('show');
    Route::patch('/{monitoringGroup}', [MobileMonitoringGroupController::class, 'update'])->name('update');
    Route::delete('/{monitoringGroup}', [MobileMonitoringGroupController::class, 'destroy'])->name('destroy');
});

Route::group(['prefix' => 'mobile/status-pages', 'as' => 'mobile.status-pages.'], function (): void {
    Route::get('/', [MobileStatusPageWorkspaceController::class, 'index'])->name('index');
    Route::get('/{statusPage}', [MobileStatusPageWorkspaceController::class, 'show'])->name('show');
    Route::patch('/{statusPage}/publication', [MobileStatusPageWorkspaceController::class, 'updatePublication'])->name('publication.update');
    Route::get('/{statusPage}/incidents', [MobileStatusPageWorkspaceController::class, 'incidents'])->name('incidents.index');
    Route::get('/{statusPage}/incidents/{incident}', [MobileStatusPageWorkspaceController::class, 'showIncident'])->name('incidents.show');
    Route::post('/{statusPage}/incidents/{incident}/updates', [MobileStatusPageWorkspaceController::class, 'storeIncidentUpdate'])->name('incidents.updates.store');
    Route::patch('/{statusPage}/incidents/{incident}/metadata', [MobileStatusPageWorkspaceController::class, 'updateMetadata'])->name('incidents.metadata.update');
    Route::patch('/{statusPage}/incidents/{incident}/review', [MobileStatusPageWorkspaceController::class, 'updateReview'])->name('incidents.review.update');
    Route::post('/{statusPage}/incidents/{incident}/follow-ups', [MobileStatusPageWorkspaceController::class, 'storeFollowUp'])->name('incidents.follow-ups.store');
    Route::patch('/{statusPage}/incidents/{incident}/follow-ups/{incidentFollowUp}', [MobileStatusPageWorkspaceController::class, 'updateFollowUp'])->name('incidents.follow-ups.update');
    Route::delete('/{statusPage}/incidents/{incident}/follow-ups/{incidentFollowUp}', [MobileStatusPageWorkspaceController::class, 'destroyFollowUp'])->name('incidents.follow-ups.destroy');
    Route::post('/{statusPage}/incidents/{incident}/timeline', [MobileStatusPageWorkspaceController::class, 'storeTimelineEvent'])->name('incidents.timeline.store');
    Route::patch('/{statusPage}/incidents/{incident}/timeline/{incidentTimelineEvent}', [MobileStatusPageWorkspaceController::class, 'updateTimelineEvent'])->name('incidents.timeline.update');
    Route::delete('/{statusPage}/incidents/{incident}/timeline/{incidentTimelineEvent}', [MobileStatusPageWorkspaceController::class, 'destroyTimelineEvent'])->name('incidents.timeline.destroy');
});

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
