<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\External\MobileOverviewController;
use App\Http\Controllers\Api\External\MobilePushDeviceController;
use App\Http\Controllers\Api\External\MonitoringDataController;
use App\Http\Controllers\Api\External\MonitoringManagementController;
use App\Http\Controllers\Api\External\TeamController;
use App\Http\Controllers\Api\External\TeamInvitationController;
use App\Http\Controllers\Api\External\TeamMemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExternalApiRouteBoundaryTest extends TestCase
{
    public function test_external_v1_routes_use_external_compatibility_adapters(): void
    {
        $routes = [
            ['/api/v1/monitorings', 'GET', MonitoringManagementController::class],
            ['/api/v1/monitorings/example/status', 'GET', MonitoringDataController::class],
            ['/api/v1/mobile/overview', 'GET', MobileOverviewController::class],
            ['/api/v1/mobile-push-devices', 'GET', MobilePushDeviceController::class],
            ['/api/v1/teams', 'GET', TeamController::class],
            ['/api/v1/teams/example/members', 'GET', TeamMemberController::class],
            ['/api/v1/teams/example/invitations', 'GET', TeamInvitationController::class],
        ];

        foreach ($routes as [$uri, $method, $controller]) {
            $route = Route::getRoutes()->match(Request::create($uri, $method));

            $this->assertStringStartsWith($controller, $route->getActionName());
        }
    }
}
