<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$frontendUrl = static function (string $path, array $query = []): string {
    $url = mb_rtrim((string) config('app.url'), '/') . $path;

    return $query === [] ? $url : $url . '?' . http_build_query($query);
};

Route::get('register', static fn (Request $request) => redirect()->away($frontendUrl('/register', $request->query())))
    ->name('register');
Route::get('login', static fn (Request $request) => redirect()->away($frontendUrl('/login', $request->query())))
    ->name('login');
Route::get('forgot-password', static fn (Request $request) => redirect()->away($frontendUrl('/forgot-password', $request->query())))
    ->name('password.request');
Route::get('reset-password/{token}', static fn (Request $request, string $token) => redirect()->away($frontendUrl('/reset-password/' . rawurlencode($token), $request->query())))
    ->name('password.reset');

Route::get('verify-email', static fn (Request $request) => redirect()->away($frontendUrl('/verify-email', $request->query())))
    ->name('verification.notice');
Route::get('confirm-password', static fn (Request $request) => redirect()->away($frontendUrl('/confirm-password', $request->query())))
    ->name('password.confirm');

Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['auth', 'signed:relative', 'throttle:6,1'])
    ->name('verification.verify');
