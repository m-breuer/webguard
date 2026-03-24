<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::redirect('/impressum', '/imprint')->name('impressum');
Route::redirect('/datenschutz', '/gdpr')->name('datenschutz');
