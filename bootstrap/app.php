<?php

declare(strict_types=1);

use App\Http\Middleware\AuthenticateInstance;
use App\Http\Middleware\CheckUserRole;
use App\Http\Middleware\SetLocaleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/status',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(SetLocaleMiddleware::class);
        $middleware->preventRequestsDuringMaintenance([
            'api/v1/internal/*',
            'api/monitorings/*',
        ]);
        $middleware->alias([
            'role' => CheckUserRole::class,
            'auth.instance' => AuthenticateInstance::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
