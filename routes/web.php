<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ApiController as AdminApiController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\ServerInstanceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HeartbeatPingController;
use App\Http\Controllers\IncidentAnalyticsController;
use App\Http\Controllers\LegacyPublicStatusPageController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\MonitoringGroupController;
use App\Http\Controllers\MonitoringNotificationPreferenceController;
use App\Http\Controllers\MonitoringOwnershipController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicLabelController;
use App\Http\Controllers\PublicStatusPageController;
use App\Http\Controllers\StatusPageController;
use App\Http\Controllers\StatusPageIncidentFollowUpController;
use App\Http\Controllers\StatusPageIncidentMetadataController;
use App\Http\Controllers\StatusPageIncidentReviewController;
use App\Http\Controllers\StatusPageIncidentTimelineController;
use App\Http\Controllers\StatusPageIncidentUpdateController;
use App\Http\Controllers\StatusPageSubscriberController;
use App\Http\Controllers\StatusPageSubscriptionController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInvitationAcceptController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\TeamMemberController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

$sessionlessPublicRoutes = [
    PreventRequestForgery::class,
    ShareErrorsFromSession::class,
    StartSession::class,
];

Route::get('/auth/github/redirect', [SocialiteController::class, 'redirectToProvider'])->name('github.redirect');
Route::get('/auth/github/callback', [SocialiteController::class, 'handleProviderCallback'])->name('github.callback');

Route::redirect('/', '/login')->name('home');
Route::match(['get', 'post'], '/locale', [LocaleController::class, 'update'])->name('locale.switch');
Route::match(['get', 'post'], '/heartbeat/{token}', HeartbeatPingController::class)->name('monitorings.heartbeat.ping');
Route::permanentRedirect('/api/docs', '/api/reference')->name('api.docs.redirect');
Route::get('/team-invitations/{token}/accept', TeamInvitationAcceptController::class)
    ->name('team-invitations.accept');

Route::get('/label/{monitoring}', PublicLabelController::class)
    ->name('public-label')
    ->scopeBindings();
Route::post('/label/{monitoring}/subscribers', [StatusPageSubscriberController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('public-label.subscribers.store')
    ->scopeBindings();
Route::get('/label/{monitoring}/subscribers/confirm/{token}', [StatusPageSubscriberController::class, 'confirm'])
    ->name('public-label.subscribers.confirm')
    ->scopeBindings();
Route::get('/label/{monitoring}/subscribers/unsubscribe/{token}', [StatusPageSubscriberController::class, 'unsubscribe'])
    ->name('public-label.subscribers.unsubscribe')
    ->scopeBindings();
Route::delete('/label/{monitoring}/subscribers/unsubscribe/{token}', [StatusPageSubscriberController::class, 'destroy'])
    ->name('public-label.subscribers.destroy')
    ->scopeBindings();
$statusPageUlidPattern = '(?i:[0-9A-HJKMNP-TV-Z]{26})';
$legacyStatusPageSlugPattern = '(?!(?i:[0-9A-HJKMNP-TV-Z]{26}))[A-Za-z0-9_-]+';

Route::post('/status/{statusPage}/subscribers', [StatusPageSubscriptionController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('public-status-pages.subscribers.store')
    ->where('statusPage', $statusPageUlidPattern);
Route::get('/status/{statusPage}/subscribers/confirm/{token}', [StatusPageSubscriptionController::class, 'confirm'])
    ->name('public-status-pages.subscribers.confirm')
    ->where('statusPage', $statusPageUlidPattern);
Route::get('/status/{statusPage}/subscribers/unsubscribe/{token}', [StatusPageSubscriptionController::class, 'unsubscribe'])
    ->name('public-status-pages.subscribers.unsubscribe')
    ->where('statusPage', $statusPageUlidPattern);
Route::delete('/status/{statusPage}/subscribers/unsubscribe/{token}', [StatusPageSubscriptionController::class, 'destroy'])
    ->name('public-status-pages.subscribers.destroy')
    ->where('statusPage', $statusPageUlidPattern);
Route::get('/status/{statusPage}', PublicStatusPageController::class)
    ->name('public-status-pages.show')
    ->where('statusPage', $statusPageUlidPattern);

Route::post('/status/{statusPageSlug}/subscribers', [LegacyPublicStatusPageController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('legacy-public-status-pages.subscribers.store')
    ->where('statusPageSlug', $legacyStatusPageSlugPattern);
Route::get('/status/{statusPageSlug}/subscribers/confirm/{token}', [LegacyPublicStatusPageController::class, 'confirm'])
    ->name('legacy-public-status-pages.subscribers.confirm')
    ->where('statusPageSlug', $legacyStatusPageSlugPattern);
Route::get('/status/{statusPageSlug}/subscribers/unsubscribe/{token}', [LegacyPublicStatusPageController::class, 'unsubscribe'])
    ->name('legacy-public-status-pages.subscribers.unsubscribe')
    ->where('statusPageSlug', $legacyStatusPageSlugPattern);
Route::delete('/status/{statusPageSlug}/subscribers/unsubscribe/{token}', [LegacyPublicStatusPageController::class, 'destroy'])
    ->name('legacy-public-status-pages.subscribers.destroy')
    ->where('statusPageSlug', $legacyStatusPageSlugPattern);
Route::get('/status/{statusPageSlug}', [LegacyPublicStatusPageController::class, 'show'])
    ->name('legacy-public-status-pages.show')
    ->where('statusPageSlug', $legacyStatusPageSlugPattern);

Route::get('/badge.js', function () {
    return response(file_get_contents(public_path('js/badge.js')))->header('Content-Type', 'application/javascript');
})->name('badge.js');

Route::middleware(['auth', 'role:member,admin'])
    ->prefix('profile')
    ->as('profile.')
    ->group(function (): void {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'verified'])->group(function (): void {

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::group(['prefix' => 'profile', 'as' => 'profile.', 'middleware' => 'role:member,admin'], function (): void {
        Route::post('/api-generate-token', [ProfileController::class, 'apiGenerateToken'])->name('api-generate-token');
        Route::delete('/api-revoke-token', [ProfileController::class, 'apiRevokeToken'])->name('api-revoke-token');
        Route::post('/notification-channels/{channel}/test', [ProfileController::class, 'sendNotificationChannelTest'])
            ->name('notification-channels.test');
    });

    Route::resource('monitorings', MonitoringController::class)->names('monitorings');
    Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
    Route::delete('/maintenance', [MaintenanceController::class, 'destroy'])->name('maintenance.destroy');
    Route::post('/monitorings/{monitoring}/team-ownership', [MonitoringOwnershipController::class, 'moveToTeam'])
        ->name('monitorings.team-ownership.store');
    Route::delete('/monitorings/{monitoring}/team-ownership', [MonitoringOwnershipController::class, 'moveToPrivate'])
        ->name('monitorings.team-ownership.destroy');
    Route::patch('/monitorings/{monitoring}/notification-preferences', [MonitoringNotificationPreferenceController::class, 'update'])
        ->name('monitorings.notification-preferences.update');
    Route::resource('teams', TeamController::class)->names('teams');
    Route::post('/teams/{team}/invitations', [TeamInvitationController::class, 'store'])
        ->name('teams.invitations.store');
    Route::delete('/teams/{team}/invitations/{teamInvitation}', [TeamInvitationController::class, 'destroy'])
        ->name('teams.invitations.destroy');
    Route::patch('/teams/{team}/members/{teamMembership}', [TeamMemberController::class, 'update'])
        ->name('teams.members.update');
    Route::delete('/teams/{team}/members/{teamMembership}', [TeamMemberController::class, 'destroy'])
        ->name('teams.members.destroy');
    Route::delete('/teams/{team}/leave', [TeamMemberController::class, 'leave'])
        ->name('teams.leave');
    Route::resource('monitoring-groups', MonitoringGroupController::class)
        ->except(['show'])
        ->parameters(['monitoring-groups' => 'monitoringGroup'])
        ->names('monitoring-groups');
    Route::post('/monitoring-groups/{monitoringGroup}/publish-status-page', [MonitoringGroupController::class, 'publishStatusPage'])
        ->name('monitoring-groups.publish-status-page');
    Route::resource('status-pages', StatusPageController::class)
        ->parameters(['status-pages' => 'statusPage'])
        ->names('status-pages');
    Route::post('/status-pages/{statusPage}/incidents/{incident}/updates', [StatusPageIncidentUpdateController::class, 'store'])
        ->name('status-pages.incident-updates.store');
    Route::patch('/status-pages/{statusPage}/incidents/{incident}/review', [StatusPageIncidentReviewController::class, 'update'])
        ->name('status-pages.incident-review.update');
    Route::patch('/status-pages/{statusPage}/incidents/{incident}/metadata', [StatusPageIncidentMetadataController::class, 'update'])
        ->name('status-pages.incident-metadata.update');
    Route::post('/status-pages/{statusPage}/incidents/{incident}/follow-ups', [StatusPageIncidentFollowUpController::class, 'store'])
        ->name('status-pages.incident-follow-ups.store');
    Route::patch('/status-pages/{statusPage}/incidents/{incident}/follow-ups/{incidentFollowUp}', [StatusPageIncidentFollowUpController::class, 'update'])
        ->name('status-pages.incident-follow-ups.update');
    Route::delete('/status-pages/{statusPage}/incidents/{incident}/follow-ups/{incidentFollowUp}', [StatusPageIncidentFollowUpController::class, 'destroy'])
        ->name('status-pages.incident-follow-ups.destroy');
    Route::post('/status-pages/{statusPage}/incidents/{incident}/timeline', [StatusPageIncidentTimelineController::class, 'store'])
        ->name('status-pages.incident-timeline.store');
    Route::patch('/status-pages/{statusPage}/incidents/{incident}/timeline/{incidentTimelineEvent}', [StatusPageIncidentTimelineController::class, 'update'])
        ->name('status-pages.incident-timeline.update');
    Route::delete('/status-pages/{statusPage}/incidents/{incident}/timeline/{incidentTimelineEvent}', [StatusPageIncidentTimelineController::class, 'destroy'])
        ->name('status-pages.incident-timeline.destroy');
    Route::get('/incidents/analytics', [IncidentAnalyticsController::class, 'index'])
        ->name('incidents.analytics');

    Route::delete('/monitorings/{monitoring}/reset', [MonitoringController::class, 'destroyResults'])
        ->name('monitorings.destroyResults');
    Route::post('/monitorings/{monitoring}/server-health-token/rotate', [MonitoringController::class, 'rotateServerHealthToken'])
        ->name('monitorings.server-health-token.rotate');

    Route::group(['prefix' => 'notifications', 'as' => 'notifications.'], function (): void {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
        Route::post('/load-more', [NotificationController::class, 'loadMore'])->name('loadMore');
    });

    Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => 'role:admin'], function (): void {
        Route::get('/', fn () => view('admin.dashboard'))->name('dashboard');
        Route::resource('/users', UserController::class)->except(['show'])->names('users');
        Route::post('/users/{user}/verify', [UserController::class, 'verify'])->name('users.verify');
        Route::resource('/packages', PackageController::class)->except(['show'])->names('packages');
        Route::resource('/server-instances', ServerInstanceController::class)->except(['show'])->names('server-instances');
        Route::resource('/apis', AdminApiController::class)->only(['index'])->names('apis');
        Route::get('/audit-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });
});

Route::group(
    ['prefix' => 'api', 'as' => 'api.', 'middleware' => 'auth'],
    function () {
        require __DIR__ . '/api/internal.php';
    }
);

Route::group(
    ['prefix' => 'api/v1/internal/ui', 'as' => 'api.v1.internal.ui.', 'middleware' => ['auth', 'verified']],
    function (): void {
        require __DIR__ . '/api/ui.php';
    }
);

require __DIR__ . '/auth.php';
