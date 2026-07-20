<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MonitoringOverviewService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, MonitoringOverviewService $monitoringOverviewService): View
    {
        /** @var User $user */
        $user = $request->user();

        if ($request->boolean('service_fragment')) {
            $serviceMap = $monitoringOverviewService->serviceMap(
                $user,
                max(1, $request->integer('service_page', 1)),
            );

            return view('components.dashboard.service-map-fragment', $serviceMap);
        }

        return view('dashboard', $monitoringOverviewService->overview(
            $user,
            max(1, $request->integer('service_page', 1)),
        ));
    }
}
