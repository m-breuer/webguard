<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ApiController as AdminApiController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\ServerInstanceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicLabelController;
use Illuminate\Support\Facades\Route;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

Route::get('/auth/github/redirect', [SocialiteController::class, 'redirectToProvider'])->name('github.redirect');
Route::get('/auth/github/callback', [SocialiteController::class, 'handleProviderCallback'])->name('github.callback');

Route::get('/', fn() => view('welcome'))->name('welcome');

Route::get('demo', fn() => view('demo'))->name('demo');

// TODO: Add content to these pages
// Route::get('/terms-of-use', fn() => view('terms-of-use'))->name('terms.show');
// Route::get('/privacy-policy', fn() => view('privacy-policy'))->name('policy.show');

// Public sitemap.xml
Route::get('/sitemap.xml', function () {
    return Sitemap::create()
        ->add(Url::create(route('welcome')))
        ->add(Url::create(route('demo')))
        // ->add(Url::create(route('terms.show')))
        // ->add(Url::create(route('policy.show')))
        ->toResponse(request());
})->name('sitemap');

Route::get('/label/{monitoring}', PublicLabelController::class)
    ->name('public-label')
    ->scopeBindings();

Route::get('/widget.js', function () {
    return response(file_get_contents(public_path('js/widget.js')))->header('Content-Type', 'application/javascript');
})->name('widget.js');

Route::middleware(['auth'])->group(function (): void {

    Route::get('/dashboard', fn() => to_route('monitorings.index'))->name('dashboard');

    Route::group(['prefix' => 'profile', 'as' => 'profile.', 'middleware' => 'role:member,admin'], function (): void {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');

        Route::post('/api-generate-token', [ProfileController::class, 'apiGenerateToken'])->name('api-generate-token');
        Route::delete('/api-revoke-token', [ProfileController::class, 'apiRevokeToken'])->name('api-revoke-token');
    });

    Route::resource('monitorings', MonitoringController::class)->names('monitorings');

    Route::delete('/monitorings/{monitoring}/reset', [MonitoringController::class, 'destroyResults'])
        ->name('monitorings.destroyResults');

    Route::group(['prefix' => 'notifications', 'as' => 'notifications.'], function (): void {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
        Route::post('/load-more', [NotificationController::class, 'loadMore'])->name('loadMore');
    });

    Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => 'role:admin'], function (): void {
        Route::get('/', fn() => view('admin.dashboard'))->name('dashboard');
        Route::resource('/users', UserController::class)->except(['show'])->names('users');
        Route::post('/users/{user}/verify', [UserController::class, 'verify'])->name('users.verify');
        Route::resource('/packages', PackageController::class)->except(['show'])->names('packages');
        Route::resource('/server-instances', ServerInstanceController::class)->except(['show'])->names('server-instances');
        Route::resource('/apis', AdminApiController::class)->only(['index'])->names('apis');
    });
});

Route::group(
    ['prefix' => 'api', 'as' => 'api.'],
    function () {
        require __DIR__ . '/api/internal.php';
    }
);

require __DIR__ . '/auth.php';
