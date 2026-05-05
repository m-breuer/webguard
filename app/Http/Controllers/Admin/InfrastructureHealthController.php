<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InfrastructureHealthService;
use Illuminate\View\View;

class InfrastructureHealthController extends Controller
{
    public function __invoke(InfrastructureHealthService $infrastructureHealthService): View
    {
        return view('admin.infrastructure-health.index', [
            'report' => $infrastructureHealthService->report(),
        ]);
    }
}
