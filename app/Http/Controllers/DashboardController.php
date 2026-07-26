<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Presenters\DashboardServicePresenter;
use App\Services\MonitoringOverviewService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        MonitoringOverviewService $monitoringOverviewService,
        DashboardServicePresenter $dashboardServicePresenter,
    ): View {
        /** @var User $user */
        $user = $request->user();

        if (! $request->boolean('async')) {
            return view('dashboard-shell');
        }

        $overview = $monitoringOverviewService->overview(
            $user,
            max(1, $request->integer('service_page', 1)),
        );
        $overview['signalRoomServices'] = $dashboardServicePresenter->present($overview['serviceReadModels']);

        return view('dashboard', $overview);
    }
}
