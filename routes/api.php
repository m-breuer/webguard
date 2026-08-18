<?php

declare(strict_types=1);

use App\Http\Controllers\Api\External\ApiKeyController;
use App\Http\Controllers\Api\External\BearerServerHealthReportController;
use App\Http\Controllers\Api\External\PublicMonitoringLocationController;
use App\Http\Controllers\Api\Mobile\MobileAuthController;
use App\Http\Controllers\Api\ServerHealthReportController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\PublicStatusPageUptimeCalendarController;
use App\Http\Middleware\RequireExternalApiAbility;
use App\Http\Middleware\TrackApiUsage;
use Illuminate\Support\Facades\Route;

Route::get('/public/monitorings/{monitoring}/badge', [ApiController::class, 'badge'])
    ->name('public.monitorings.badge');
Route::get('/public/monitorings/{monitoring}/uptime-calendar', [ApiController::class, 'uptimeCalendar'])
    ->name('public.monitorings.uptime-calendar');
Route::get('/public/status-pages/{statusPage}/uptime-calendar', PublicStatusPageUptimeCalendarController::class)
    ->name('public.status-pages.uptime-calendar');

Route::post('/v1/server-health/{token}', ServerHealthReportController::class)
    ->middleware('throttle:60,1')
    ->name('v1.server-health.store');

Route::get('/v1/public/monitoring-locations', PublicMonitoringLocationController::class)
    ->middleware('throttle:60,1')
    ->name('v1.public.monitoring-locations.index');

Route::post('/v1/server-health/monitorings/{monitoring}', BearerServerHealthReportController::class)
    ->middleware(['auth:sanctum', 'api-key.ability:server-health:write', 'throttle:60,1'])
    ->name('v1.server-health.bearer.store');

Route::group(['prefix' => 'v1/api-keys', 'as' => 'v1.api-keys.', 'middleware' => ['auth:sanctum', 'api-key.manage']], function (): void {
    Route::get('/', [ApiKeyController::class, 'index'])->name('index');
    Route::post('/', [ApiKeyController::class, 'store'])->name('store');
    Route::get('/{apiKey}', [ApiKeyController::class, 'show'])->whereNumber('apiKey')->name('show');
    Route::delete('/{apiKey}', [ApiKeyController::class, 'destroy'])->whereNumber('apiKey')->name('destroy');
});

Route::post('/mobile/login', [MobileAuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('mobile.login');

Route::group(['prefix' => 'mobile', 'as' => 'mobile.', 'middleware' => ['auth:sanctum']], function (): void {
    Route::get('/me', [MobileAuthController::class, 'me'])->name('me');
    Route::post('/logout', [MobileAuthController::class, 'logout'])->name('logout');
});

Route::group(['prefix' => 'v1', 'as' => 'v1.', 'middleware' => ['auth:sanctum', TrackApiUsage::class, RequireExternalApiAbility::class]], function (): void {
    require __DIR__ . '/api/external.php';
});

require __DIR__ . '/api/instance.php';
