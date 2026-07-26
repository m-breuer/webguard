<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringStatus;
use App\Enums\MonitoringType;
use App\Http\Requests\MonitoringRequest;
use App\Jobs\DeleteMonitoringResults;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\ServerInstance;
use App\Models\Team;
use App\Models\User;
use App\Services\Notifications\MonitoringNotificationPreferenceResolver;
use App\Services\RegionalConsensusService;
use App\Support\MonitoringPayload;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Class MonitoringController
 *
 * Handles CRUD operations for monitorings including creation, update, deletion,
 * and auxiliary functionality such as resetting results.
 */
class MonitoringController extends Controller
{
    /**
     * Display a listing of the user's monitorings.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View The view displaying the list of monitorings.
     */
    public function index(
        Request $request,
        MonitoringNotificationPreferenceResolver $monitoringNotificationPreferenceResolver
    ): View {
        /** @var User $currentUser */
        $currentUser = $request->user()->loadMissing('package');

        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'types' => ['nullable', 'string', function ($attribute, $value, $fail) {
                $types = explode(',', $value);
                foreach ($types as $type) {
                    if (! MonitoringType::tryFrom($type)) {
                        $fail(__('monitoring.validation.invalid_type', ['type' => $type]));
                    }
                }
            }],
            'health' => ['nullable', 'string', function ($attribute, $value, $fail) {
                foreach (explode(',', $value) as $status) {
                    if (! MonitoringStatus::tryFrom($status)) {
                        $fail(__('monitoring.validation.invalid_status', ['status' => $status]));
                    }
                }
            }],
            'lifecycle' => ['nullable', 'string', Rule::enum(MonitoringLifecycleStatus::class)],
            'group_id' => [
                'nullable',
                'string',
                Rule::exists('monitoring_groups', 'id')->where('user_id', $currentUser->id),
            ],
            'team_id' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($currentUser): void {
                    if (blank($value)) {
                        return;
                    }

                    if (! Team::query()->visibleTo($currentUser)->whereKey((string) $value)->exists()) {
                        $fail(__('team.validation.not_member'));
                    }
                },
            ],
            'ownership' => ['nullable', 'string', Rule::in(['all', 'private', 'team'])],
            'maintenance' => ['nullable', 'string', Rule::in(['active'])],
        ]);

        $query = Monitoring::query()
            ->select([
                'id',
                'name',
                'target',
                'type',
                'status',
                'public_label_enabled',
                'maintenance_from',
                'maintenance_until',
            ]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('target', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('port', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('keyword', 'like', sprintf('%%%s%%', $search));
            });
        }

        if ($request->filled('types')) {
            $types = explode(',', (string) $request->input('types'));
            $query->whereIn('type', $types);
        }

        if ($request->filled('lifecycle')) {
            $query->where('status', $request->lifecycle);
        }

        if ($request->filled('group_id')) {
            $query->whereHas('groups', function ($builder) use ($request): void {
                $builder->where('monitoring_groups.id', $request->string('group_id')->toString());
            });
        }

        if ($request->filled('team_id')) {
            $query->where('team_id', $request->string('team_id')->toString());
        }

        if ($request->input('ownership') === 'private') {
            $query->whereNull('team_id');
        } elseif ($request->input('ownership') === 'team') {
            $query->whereNotNull('team_id');
        }

        if ($request->filled('health')) {
            $healthStatuses = explode(',', (string) $request->input('health'));

            $query->where(function (Builder $builder) use ($healthStatuses): void {
                if (in_array(MonitoringStatus::UP->value, $healthStatuses, true)) {
                    $builder->orWhereHas('latestResponseResult', fn ($query) => $query->where('status', MonitoringStatus::UP));
                }

                if (in_array(MonitoringStatus::DOWN->value, $healthStatuses, true)) {
                    $builder->orWhereHas('latestResponseResult', fn ($query) => $query->where('status', MonitoringStatus::DOWN));
                }

                if (in_array(MonitoringStatus::UNKNOWN->value, $healthStatuses, true)) {
                    $builder->orWhereDoesntHave('latestResponseResult')
                        ->orWhereHas('latestResponseResult', fn ($query) => $query->where('status', MonitoringStatus::UNKNOWN));
                }
            });
        }

        if ($request->input('maintenance') === 'active') {
            $query->whereNotNull('maintenance_from')
                ->where('maintenance_from', '<=', now())
                ->where(function (Builder $builder): void {
                    $builder->whereNull('maintenance_until')
                        ->orWhere('maintenance_until', '>=', now());
                });
        }

        $query->orderBy('status');

        match ($request->input('sort')) {
            'name_desc' => $query->orderByDesc('name'),
            'created_asc' => $query->oldest('monitorings.created_at'),
            'created_desc' => $query->latest('monitorings.created_at'),
            default => $query->orderBy('name'),
        };

        $summaryMonitoringIds = (clone $query)->select('id')->reorder()->pluck('id')->values();
        $lengthAwarePaginator = $query->withMaintenanceWindowState()->paginate(5);
        $hasActiveFilters = $request->filled('search')
            || $request->filled('types')
            || $request->filled('lifecycle')
            || $request->filled('group_id')
            || $request->filled('team_id')
            || $request->filled('ownership')
            || $request->filled('health')
            || $request->filled('maintenance');
        $privateMonitoringsTotal = $currentUser->monitorings()->whereNull('team_id')->count();
        $monitoringsTotal = $hasActiveFilters
            ? Monitoring::query()->count()
            : $lengthAwarePaginator->total();
        $monitoringLimit = (int) $currentUser->package->monitoring_limit;
        $canCreateMonitoring = ! $currentUser->isDemo()
            && ($privateMonitoringsTotal < $monitoringLimit || $currentUser->administeredTeams()->exists());

        if (! $hasActiveFilters && $monitoringsTotal === 0) {
            $request->attributes->set('unread_notifications_count', 0);
        }

        $maintenanceStatusMap = $lengthAwarePaginator->getCollection()->mapWithKeys(function ($monitoring) {
            return [$monitoring->id => $monitoring->isUnderMaintenance()];
        });
        $openIncidentCount = Incident::query()
            ->whereNull('up_at')
            ->whereHas('monitoring')
            ->count();
        $statusPageCount = $currentUser->statusPages()->count();
        $modalForm = $request->string('modal')->toString();
        $modalMonitoring = null;
        $modalFormData = [];

        if ($modalForm === 'monitoring-create') {
            $modalFormData = [
                'types' => MonitoringType::cases(),
                'serverInstances' => ServerInstance::query()->active()->orderBy('code')->get(['code']),
                'enabledNotificationChannels' => $currentUser->enabledNotificationChannelKeys(),
                'monitoringGroups' => $currentUser->monitoringGroups()->orderBy('name')->get(['id', 'name']),
                'adminTeams' => $currentUser->administeredTeams()->orderBy('name')->get(['teams.id', 'teams.name']),
            ];
        } elseif ($modalForm === 'monitoring-edit' && $request->filled('monitoring')) {
            $modalMonitoring = Monitoring::query()->findOrFail($request->string('monitoring')->toString());
            abort_unless($modalMonitoring->isManageableBy($currentUser), 403);
            $modalMonitoring->loadMissing('groups', 'team');
            $modalFormData = [
                'types' => MonitoringType::cases(),
                'serverInstances' => ServerInstance::query()
                    ->where(function ($query) use ($modalMonitoring): void {
                        $query->where('is_active', true)
                            ->orWhereIn('code', $modalMonitoring->preferredLocationCodes());
                    })
                    ->orderBy('code')
                    ->get(['code']),
                'enabledNotificationChannels' => $currentUser->enabledNotificationChannelKeys(),
                'monitoringGroups' => $modalMonitoring->isPrivateOwned()
                    ? $currentUser->monitoringGroups()->orderBy('name')->get(['id', 'name'])
                    : collect(),
                'adminTeams' => $currentUser->administeredTeams()->orderBy('name')->get(['teams.id', 'teams.name']),
                'notificationPreference' => $monitoringNotificationPreferenceResolver->preferenceFor($modalMonitoring, $currentUser),
            ];
        }

        return view('monitorings.index', [
            'currentUser' => $currentUser,
            'monitorings' => $lengthAwarePaginator,
            'summaryMonitoringIds' => $summaryMonitoringIds,
            'monitoringsTotal' => $monitoringsTotal,
            'monitoringLimit' => $monitoringLimit,
            'privateMonitoringsTotal' => $privateMonitoringsTotal,
            'canCreateMonitoring' => $canCreateMonitoring,
            'maintenanceStatusMap' => $maintenanceStatusMap,
            'openIncidentCount' => $openIncidentCount,
            'statusPageCount' => $statusPageCount,
            'monitoringGroups' => $currentUser->monitoringGroups()->orderBy('name')->get(['id', 'name']),
            'teams' => Team::query()->visibleTo($currentUser)->orderBy('name')->get(['id', 'name']),
            'modalForm' => $modalForm,
            'modalMonitoring' => $modalMonitoring,
            'modalFormData' => $modalFormData,
        ]);
    }

    /**
     * Show the form for creating a new monitoring.
     *
     * @return View|RedirectResponse The view for creating a monitoring, or a redirect response if the monitoring limit is reached.
     */
    public function create(): View|RedirectResponse
    {
        abort_if(Auth::user()->isDemo(), 403);

        $adminTeams = Auth::user()->administeredTeams()->orderBy('name')->get(['teams.id', 'teams.name']);

        if (Auth::user()->monitorings()->whereNull('team_id')->count() >= Auth::user()->package->monitoring_limit
            && $adminTeams->isEmpty()) {
            return to_route('monitorings.index')
                ->withErrors(['limit' => __('monitoring.messages.limit_reached')]);
        }

        $types = MonitoringType::cases();
        $serverInstances = ServerInstance::query()->active()->orderBy('code')->get(['code']);

        if ($serverInstances->isEmpty()) {
            return to_route('monitorings.index')
                ->withErrors(['preferred_location' => __('monitoring.messages.no_server_instances')]);
        }

        return view('monitorings.create', [
            'types' => $types,
            'serverInstances' => $serverInstances,
            'enabledNotificationChannels' => Auth::user()->enabledNotificationChannelKeys(),
            'monitoringGroups' => Auth::user()->monitoringGroups()->orderBy('name')->get(['id', 'name']),
            'adminTeams' => $adminTeams,
        ]);
    }

    /**
     * Store a newly created monitoring in storage.
     *
     * @param  MonitoringRequest  $monitoringRequest  The request containing validated monitoring data.
     * @return RedirectResponse A redirect response after storing the monitoring.
     */
    public function store(MonitoringRequest $monitoringRequest): RedirectResponse
    {
        abort_if(Auth::user()->isDemo(), 403);

        if (Auth::user()->monitorings()->whereNull('team_id')->count() >= Auth::user()->package->monitoring_limit
            && blank($monitoringRequest->input('team_id'))) {
            return to_route('monitorings.index')
                ->withErrors(['limit' => __('monitoring.messages.limit_reached')]);
        }

        $validated = $monitoringRequest->validated();
        $groupIds = $validated['group_ids'] ?? [];
        $teamId = $validated['team_id'] ?? null;
        unset($validated['group_ids']);
        unset($validated['team_id']);

        $validated = MonitoringPayload::prepareStore($validated);

        if ($teamId) {
            $validated['user_id'] = null;
            $validated['team_id'] = $teamId;
            $validated['created_by_user_id'] = Auth::id();

            $monitoring = Monitoring::query()->create($validated);
        } else {
            $monitoring = Auth::user()->monitorings()->create($validated);
            $monitoring->groups()->sync($groupIds);
        }

        return to_route('monitorings.index')->with('success', __('monitoring.messages.created'));
    }

    /**
     * Display the specified monitoring detail view.
     *
     * @param  Monitoring  $monitoring  The monitoring instance to display.
     * @return View The view displaying the monitoring details.
     */
    public function show(Monitoring $monitoring, RegionalConsensusService $regionalConsensusService): View
    {
        /** @var User $user */
        $user = Auth::user();
        $monitoring->loadMissing([
            'domainResult',
            'groups',
            'latestIncident',
            'latestResponseResult',
            'sslResult',
            'statusPageComponents.statusPage',
            'team.users',
            'user',
        ]);

        $notificationRecipients = $monitoring->team_id !== null
            ? ($monitoring->team?->users ?? collect())
            : collect([$monitoring->user ?? $user]);

        return view('monitorings.show', [
            'monitoring' => $monitoring,
            'canManageMonitoring' => $monitoring->isManageableBy($user) && ! $user->isDemo(),
            'notificationRecipients' => $notificationRecipients,
            'regionalConsensus' => count($monitoring->preferredLocationCodes()) > 1
                ? $regionalConsensusService->snapshot($monitoring)
                : null,
        ]);
    }

    /**
     * Show the form for editing the specified monitoring.
     *
     * @param  Monitoring  $monitoring  The monitoring instance to edit.
     * @return View The view for editing the monitoring.
     */
    public function edit(
        Monitoring $monitoring,
        MonitoringNotificationPreferenceResolver $monitoringNotificationPreferenceResolver
    ): View {
        abort_if(Auth::user()->isDemo(), 403);
        abort_unless($monitoring->isManageableBy(Auth::user()), 403);

        /** @var User $user */
        $user = Auth::user();
        $monitoring->loadMissing('groups', 'team');
        $types = MonitoringType::cases();
        $serverInstances = ServerInstance::query()
            ->where(function ($query) use ($monitoring): void {
                $query->where('is_active', true)
                    ->orWhereIn('code', $monitoring->preferredLocationCodes());
            })
            ->orderBy('code')
            ->get(['code']);

        return view('monitorings.edit', [
            'monitoring' => $monitoring,
            'types' => $types,
            'serverInstances' => $serverInstances,
            'enabledNotificationChannels' => $user->enabledNotificationChannelKeys(),
            'monitoringGroups' => $monitoring->isPrivateOwned()
                ? $user->monitoringGroups()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'adminTeams' => $user->administeredTeams()->orderBy('name')->get(['teams.id', 'teams.name']),
            'notificationPreference' => $monitoringNotificationPreferenceResolver->preferenceFor($monitoring, $user),
        ]);
    }

    /**
     * Update the specified monitoring in storage.
     *
     * @param  MonitoringRequest  $monitoringRequest  The request containing validated monitoring data.
     * @param  Monitoring  $monitoring  The monitoring instance to update.
     * @return RedirectResponse A redirect response after updating the monitoring.
     */
    public function update(MonitoringRequest $monitoringRequest, Monitoring $monitoring): RedirectResponse
    {
        abort_if(Auth::user()->isDemo(), 403);
        abort_unless($monitoring->isManageableBy(Auth::user()), 403);

        $validated = $monitoringRequest->validated();
        $groupIds = $validated['group_ids'] ?? [];
        unset($validated['group_ids']);
        unset($validated['team_id']);

        $validated = MonitoringPayload::prepareUpdate($validated, $monitoring);

        if (! isset($validated['public_label_enabled']) || ! $validated['public_label_enabled']) {
            $validated['public_label_enabled'] = false;
        }

        $monitoring->update($validated);
        if ($monitoring->isPrivateOwned()) {
            $monitoring->groups()->sync($groupIds);
        }

        return to_route('monitorings.show', $monitoring)->with('success', __('monitoring.messages.updated'));
    }

    /**
     * Remove the specified monitoring from storage.
     *
     * @param  Monitoring  $monitoring  The monitoring instance to delete.
     * @return RedirectResponse A redirect response after deleting the monitoring.
     */
    public function destroy(Monitoring $monitoring): RedirectResponse
    {
        abort_if(Auth::user()->isDemo(), 403);
        abort_unless($monitoring->isManageableBy(Auth::user()), 403);

        $monitoring->delete();

        return to_route('monitorings.index')->with('success', __('monitoring.messages.deleted'));
    }

    /**
     * Delete all results associated with a monitoring.
     *
     * @param  Monitoring  $monitoring  The monitoring instance to delete results for.
     * @return RedirectResponse A redirect response after deleting the results.
     */
    public function destroyResults(Monitoring $monitoring): RedirectResponse
    {
        abort_if(Auth::user()->isDemo(), 403);
        abort_unless($monitoring->isManageableBy(Auth::user()), 403);

        if (cache()->getStore() instanceof TaggableStore) {
            cache()->tags(['monitoring:' . $monitoring->id])->flush();
        }

        activity('monitoring')
            ->performedOn($monitoring)
            ->event('results_deleted')
            ->withProperties(['action' => 'monitoring_results_deleted'])
            ->log('monitoring_results_deleted');

        dispatch(new DeleteMonitoringResults($monitoring));

        return to_route('monitorings.show', $monitoring)->with('success', __('monitoring.messages.results_deleted'));
    }
}
