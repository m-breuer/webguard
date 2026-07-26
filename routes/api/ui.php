<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Internal\Ui\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', DashboardController::class)->name('dashboard');
