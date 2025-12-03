<?php

namespace App\Providers;

use App\Http\View\Composers\NotificationComposer;
use App\Models\Incident;
use App\Models\MonitoringResponse;
use App\Observers\IncidentObserver;
use App\Observers\MonitoringResponseObserver;
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
        View::composer('layouts.navigation', NotificationComposer::class);
    }
}
