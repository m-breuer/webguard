<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Mobile\MobileAuthController;
use App\Http\Controllers\Api\ServerHealthReportController;
use App\Http\Controllers\ApiController;
use App\Http\Middleware\TrackApiUsage;
use Illuminate\Support\Facades\Route;

Route::get('/public/monitorings/{monitoring}/badge', [ApiController::class, 'badge'])
    ->name('public.monitorings.badge');
Route::get('/public/monitorings/{monitoring}/uptime-calendar', [ApiController::class, 'uptimeCalendar'])
    ->name('public.monitorings.uptime-calendar');

Route::post('/v1/server-health/{token}', ServerHealthReportController::class)
    ->middleware('throttle:60,1')
    ->name('v1.server-health.store');

Route::post('/mobile/login', [MobileAuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('mobile.login');

Route::group(['prefix' => 'mobile', 'as' => 'mobile.', 'middleware' => ['auth:sanctum']], function (): void {
    Route::get('/me', [MobileAuthController::class, 'me'])->name('me');
    Route::post('/logout', [MobileAuthController::class, 'logout'])->name('logout');
});

Route::group(['prefix' => 'v1', 'as' => 'v1.', 'middleware' => ['auth:sanctum', TrackApiUsage::class]], function (): void {
    require __DIR__ . '/api/external.php';
});

require __DIR__ . '/api/instance.php';
