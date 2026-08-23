<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\RedirectWwwToNonWww;
use App\Http\View\Composers\NotificationComposer;
use App\Models\Incident;
use App\Models\MaintenanceWindow;
use App\Models\Monitoring;
use App\Models\MonitoringDailyResult;
use App\Models\MonitoringDomainResult;
use App\Models\MonitoringNotification;
use App\Models\MonitoringResponse;
use App\Models\MonitoringSslResult;
use App\Models\NotificationChannelDelivery;
use App\Models\PersonalAccessToken;
use App\Models\StatusPage;
use App\Models\StatusPageComponent;
use App\Models\TeamMembership;
use App\Models\User;
use App\Observers\IncidentObserver;
use App\Observers\MonitoringResponseObserver;
use App\Observers\MonitoringStatsCacheObserver;
use App\Observers\OperationsOverviewCacheObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

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
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

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
        MonitoringDailyResult::observe(MonitoringStatsCacheObserver::class);
        MonitoringDomainResult::observe(MonitoringStatsCacheObserver::class);
        MonitoringSslResult::observe(MonitoringStatsCacheObserver::class);
        TeamMembership::observe(OperationsOverviewCacheObserver::class);
        MaintenanceWindow::observe(OperationsOverviewCacheObserver::class);
        MonitoringNotification::observe(OperationsOverviewCacheObserver::class);
        NotificationChannelDelivery::observe(OperationsOverviewCacheObserver::class);
        StatusPage::observe(OperationsOverviewCacheObserver::class);
        StatusPageComponent::observe(OperationsOverviewCacheObserver::class);
        User::observe(UserObserver::class);
        View::composer('layouts.navigation', NotificationComposer::class);

        JsonResource::withoutWrapping();
    }
}
