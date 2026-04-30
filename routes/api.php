<?php

declare(strict_types=1);

use App\Http\Controllers\ApiController;
use App\Http\Middleware\TrackApiUsage;
use Illuminate\Support\Facades\Route;

Route::get('/public/monitorings/{monitoring}/widget', [ApiController::class, 'widget'])
    ->name('public.monitorings.widget');

Route::group(['prefix' => 'v1', 'as' => 'v1.', 'middleware' => ['auth:sanctum', TrackApiUsage::class]], function (): void {
    require __DIR__ . '/api/external.php';
});

require __DIR__ . '/api/instance.php';
