<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::redirect('/impressum', '/imprint')->name('impressum');
Route::redirect('/agb', '/terms-of-use')->name('agb');
Route::redirect('/privacy-policy', '/gdpr')->name('privacy-policy');
