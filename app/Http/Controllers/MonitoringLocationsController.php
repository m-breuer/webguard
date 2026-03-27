<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ServerInstance;
use Illuminate\View\View;

class MonitoringLocationsController extends Controller
{
    public function __invoke(): View
    {
        $locations = ServerInstance::query()
            ->active()
            ->orderBy('code')
            ->get(['code', 'ip_address']);

        return view('monitoring-locations', [
            'locations' => $locations,
        ]);
    }
}
