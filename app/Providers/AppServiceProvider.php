<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\View\Composers\NotificationComposer;
use App\Models\Incident;
use App\Models\MonitoringResponse;
use App\Models\User;
use App\Observers\IncidentObserver;
use App\Observers\MonitoringResponseObserver;
use App\Observers\UserObserver;
use Illuminate\Http\Resources\Json\JsonResource;
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
    public function boot(): void
    {
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        Incident::observe(IncidentObserver::class);
        MonitoringResponse::observe(MonitoringResponseObserver::class);
        User::observe(UserObserver::class);
        View::composer('layouts.navigation', NotificationComposer::class);

        JsonResource::withoutWrapping();
    }
}
