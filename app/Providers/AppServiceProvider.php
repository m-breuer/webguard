<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\RedirectWwwToNonWww;
use App\Http\View\Composers\NotificationComposer;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\TeamMembership;
use App\Models\User;
use App\Observers\IncidentObserver;
use App\Observers\MonitoringResponseObserver;
use App\Observers\OperationsOverviewCacheObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Router $router): void
    {
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        $router->pushMiddlewareToGroup('web', RedirectWwwToNonWww::class);

        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            $temporarySignedPath = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
                absolute: false
            );

            return url($temporarySignedPath);
        });

        Model::preventLazyLoading(! app()->isProduction());

        Incident::observe(IncidentObserver::class);
        Monitoring::observe(OperationsOverviewCacheObserver::class);
        MonitoringResponse::observe(MonitoringResponseObserver::class);
        TeamMembership::observe(OperationsOverviewCacheObserver::class);
        User::observe(UserObserver::class);
        View::composer('layouts.navigation', NotificationComposer::class);

        JsonResource::withoutWrapping();
    }
}
