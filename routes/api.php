<?php

declare(strict_types=1);

use App\Http\Middleware\TrackApiUsage;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1', 'as' => 'v1.', 'middleware' => ['auth:sanctum', TrackApiUsage::class]], function (): void {
    require __DIR__ . '/api/external.php';
});

require __DIR__ . '/api/instance.php';
