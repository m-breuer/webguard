<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DemoMonitoringController extends Controller
{
    public function index(): View
    {
        $demoUser = $this->demoUser();
        $lengthAwarePaginator = $this->demoMonitoringsQuery($demoUser)
            ->orderBy('status')
            ->orderBy('name')
            ->paginate(10);

        return view('admin.demo-monitorings.index', [
            'demoUser' => $demoUser,
            'monitorings' => $lengthAwarePaginator,
        ]);
    }

    private function demoUser(): User
    {
        return User::query()
            ->where('role', UserRole::DEMO)
            ->firstOrFail();
    }

    /**
     * @return Builder<Monitoring>
     */
    private function demoMonitoringsQuery(User $demoUser): Builder
    {
        return Monitoring::query()
            ->withoutGlobalScope('user')
            ->where('user_id', $demoUser->id);
    }
}
