<?php

namespace App\Http\Controllers;

use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Http\Requests\MonitoringRequest;
use App\Jobs\DeleteMonitoringResults;
use App\Models\Monitoring;
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
    public function index(Request $request): View
    {
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
            'lifecycle' => ['nullable', 'string', Rule::enum(MonitoringLifecycleStatus::class)],
        ]);

        $query = Monitoring::query();

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

        $query->orderBy('status');

        match ($request->input('sort')) {
            'name_desc' => $query->orderByDesc('name'),
            'created_asc' => $query->oldest('monitorings.created_at'),
            'created_desc' => $query->latest('monitorings.created_at'),
            default => $query->orderBy('name'),
        };

        $lengthAwarePaginator = $query->paginate(5);

        $maintenanceStatusMap = $lengthAwarePaginator->getCollection()->mapWithKeys(function ($monitoring) {
            return [$monitoring->id => $monitoring->isUnderMaintenance()];
        });

        return view('monitorings.index', [
            'monitorings' => $lengthAwarePaginator,
            'monitoringsTotal' => Auth::user()->monitorings()->count(),
            'maintenanceStatusMap' => $maintenanceStatusMap,
        ]);
    }

    /**
     * Show the form for creating a new monitoring.
     *
     * @return View|RedirectResponse The view for creating a monitoring, or a redirect response if the monitoring limit is reached.
     */
    public function create(): View|RedirectResponse
    {
        abort_if(Auth::user()->isGuest(), 403);

        if (Auth::user()->monitorings()->count() >= Auth::user()->package->monitoring_limit) {
            return to_route('monitorings.index')
                ->withErrors(['limit' => __('monitoring.messages.limit_reached')]);
        }

        $types = MonitoringType::cases();

        return view('monitorings.create', ['types' => $types]);
    }

    /**
     * Store a newly created monitoring in storage.
     *
     * @param  MonitoringRequest  $monitoringRequest  The request containing validated monitoring data.
     * @return RedirectResponse A redirect response after storing the monitoring.
     */
    public function store(MonitoringRequest $monitoringRequest): RedirectResponse
    {
        abort_if(Auth::user()->isGuest(), 403);

        if (Auth::user()->monitorings()->count() >= Auth::user()->package->monitoring_limit) {
            return to_route('monitorings.index')
                ->withErrors(['limit' => __('monitoring.messages.limit_reached')]);
        }

        $validated = $monitoringRequest->validated();

        Auth::user()->monitorings()->create($validated);

        return to_route('monitorings.index')->with('success', __('monitoring.messages.created'));
    }

    /**
     * Display the specified monitoring detail view.
     *
     * @param  Monitoring  $monitoring  The monitoring instance to display.
     * @return View The view displaying the monitoring details.
     */
    public function show(Monitoring $monitoring): View
    {
        return view('monitorings.show', [
            'monitoring' => $monitoring,
        ]);
    }

    /**
     * Show the form for editing the specified monitoring.
     *
     * @param  Monitoring  $monitoring  The monitoring instance to edit.
     * @return View The view for editing the monitoring.
     */
    public function edit(Monitoring $monitoring): View
    {
        abort_if(Auth::user()->isGuest(), 403);

        $types = MonitoringType::cases();

        return view('monitorings.edit', ['monitoring' => $monitoring, 'types' => $types]);
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
        abort_if(Auth::user()->isGuest(), 403);

        $validated = $monitoringRequest->validated();

        if (! isset($validated['public_label_enabled']) || ! $validated['public_label_enabled']) {
            $validated['public_label_enabled'] = false;
        }

        $monitoring->update($validated);

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
        abort_if(Auth::user()->isGuest(), 403);

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
        abort_if(Auth::user()->isGuest(), 403);

        if (cache()->getStore() instanceof TaggableStore) {
            cache()->tags(['monitoring:'.$monitoring->id])->flush();
        }

        dispatch(new DeleteMonitoringResults($monitoring));

        return to_route('monitorings.show', $monitoring)->with('success', __('monitoring.messages.results_deleted'));
    }
}
