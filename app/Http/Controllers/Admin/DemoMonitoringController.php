<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MonitoringType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DemoMonitoringRequest;
use App\Models\Monitoring;
use App\Models\ServerInstance;
use App\Models\User;
use App\Support\MonitoringPayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
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

    public function create(): View|RedirectResponse
    {
        $demoUser = $this->demoUser()->loadMissing('package');

        if ($this->demoMonitoringsQuery($demoUser)->count() >= (int) $demoUser->package->monitoring_limit) {
            return to_route('admin.demo-monitorings.index')
                ->withErrors(['limit' => __('monitoring.messages.limit_reached')]);
        }

        $serverInstances = ServerInstance::query()->active()->orderBy('code')->get(['code']);

        if ($serverInstances->isEmpty()) {
            return to_route('admin.demo-monitorings.index')
                ->withErrors(['preferred_location' => __('monitoring.messages.no_server_instances')]);
        }

        return view('admin.demo-monitorings.create', [
            'demoUser' => $demoUser,
            'types' => MonitoringType::cases(),
            'serverInstances' => $serverInstances,
            'enabledNotificationChannels' => $demoUser->enabledNotificationChannelKeys(),
        ]);
    }

    public function store(DemoMonitoringRequest $demoMonitoringRequest): RedirectResponse
    {
        $demoUser = $this->demoUser()->loadMissing('package');

        if ($this->demoMonitoringsQuery($demoUser)->count() >= (int) $demoUser->package->monitoring_limit) {
            return to_route('admin.demo-monitorings.index')
                ->withErrors(['limit' => __('monitoring.messages.limit_reached')]);
        }

        $validated = MonitoringPayload::prepareStore($demoMonitoringRequest->validated());
        $validated['user_id'] = $demoUser->id;

        Monitoring::query()
            ->withoutGlobalScope('user')
            ->create($validated);

        return to_route('admin.demo-monitorings.index')
            ->with('success', __('admin.demo_monitorings.messages.created'));
    }

    public function edit(string $demoMonitoring): View
    {
        $demoUser = $this->demoUser();
        $monitoring = $this->demoMonitoring($demoUser, $demoMonitoring);
        $serverInstances = ServerInstance::query()
            ->where(function ($query) use ($monitoring): void {
                $query->where('is_active', true)
                    ->orWhereIn('code', $monitoring->preferredLocationCodes());
            })
            ->orderBy('code')
            ->get(['code']);

        return view('admin.demo-monitorings.edit', [
            'demoUser' => $demoUser,
            'monitoring' => $monitoring,
            'types' => MonitoringType::cases(),
            'serverInstances' => $serverInstances,
            'enabledNotificationChannels' => $demoUser->enabledNotificationChannelKeys(),
        ]);
    }

    public function update(DemoMonitoringRequest $demoMonitoringRequest, string $demoMonitoring): RedirectResponse
    {
        $demoUser = $this->demoUser();
        $monitoring = $this->demoMonitoring($demoUser, $demoMonitoring);
        $validated = $demoMonitoringRequest->validated();

        $validated = MonitoringPayload::prepareUpdate($validated, $monitoring);

        if (! isset($validated['public_label_enabled']) || ! $validated['public_label_enabled']) {
            $validated['public_label_enabled'] = false;
        }

        $monitoring->update($validated);

        return to_route('admin.demo-monitorings.index')
            ->with('success', __('admin.demo_monitorings.messages.updated'));
    }

    public function destroy(string $demoMonitoring): RedirectResponse
    {
        $demoUser = $this->demoUser();
        $monitoring = $this->demoMonitoring($demoUser, $demoMonitoring);

        $monitoring->delete();

        return to_route('admin.demo-monitorings.index')
            ->with('success', __('admin.demo_monitorings.messages.deleted'));
    }

    private function demoUser(): User
    {
        return User::query()
            ->where('role', UserRole::DEMO)
            ->firstOrFail();
    }

    private function demoMonitoring(User $demoUser, string $monitoringId): Monitoring
    {
        return $this->demoMonitoringsQuery($demoUser)
            ->whereKey($monitoringId)
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
