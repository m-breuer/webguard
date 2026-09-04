<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Internal\Ui\AuthWorkspaceController;
use App\Http\Middleware\MeasureInternalUiRequest;
use Illuminate\Support\Facades\Route;

Route::middleware(MeasureInternalUiRequest::class)->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/options', [AuthWorkspaceController::class, 'options'])->name('options');
        Route::post('/login', [AuthWorkspaceController::class, 'login'])->name('login');
        Route::post('/register', [AuthWorkspaceController::class, 'register'])->name('register');
        Route::get('/demo-credentials', [AuthWorkspaceController::class, 'demoCredentials'])->name('demo-credentials');
        Route::post('/forgot-password', [AuthWorkspaceController::class, 'sendPasswordResetLink'])
            ->middleware('throttle:5,1')
            ->name('password.email');
        Route::post('/reset-password', [AuthWorkspaceController::class, 'resetPassword'])->name('password.reset');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/email/verification-notification', [AuthWorkspaceController::class, 'resendVerification'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
        Route::post('/confirm-password', [AuthWorkspaceController::class, 'confirmPassword'])->name('password.confirm');
    });
});
