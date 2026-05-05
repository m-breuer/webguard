<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServerInstanceRequest;
use App\Http\Requests\UpdateServerInstanceRequest;
use App\Models\Monitoring;
use App\Models\ServerInstance;
use App\Support\Admin\AsyncTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\View\View;

class ServerInstanceController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $validated = $request->validate(AsyncTable::requestRules([
            'search' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'string', 'in:1,0'],
            'health' => ['nullable', 'string', 'in:healthy,stale,never_seen,inactive'],
        ], ['code', 'is_active', 'last_seen_at', 'created_at', 'updated_at']));
        $table = AsyncTable::options($validated, 'code', 'asc', 10);
        $staleCutoff = Date::now()->subMinutes(max(1, (int) config('monitoring.instance_stale_after_minutes', 10)));

        $lengthAwarePaginator = ServerInstance::query()
            ->when($validated['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder->where('code', 'like', '%' . $search . '%')
                        ->orWhere('ip_address', 'like', '%' . $search . '%');
                });
            })
            ->when(isset($validated['is_active']), fn (Builder $builder): Builder => $builder->where('is_active', (bool) $validated['is_active']))
            ->when($validated['health'] ?? null, function (Builder $builder, string $health) use ($staleCutoff): Builder {
                return match ($health) {
                    'healthy' => $builder->where('is_active', true)->whereNotNull('last_seen_at')->where('last_seen_at', '>=', $staleCutoff),
                    'stale' => $builder->where('is_active', true)->whereNotNull('last_seen_at')->where('last_seen_at', '<', $staleCutoff),
                    'never_seen' => $builder->where('is_active', true)->whereNull('last_seen_at'),
                    'inactive' => $builder->where('is_active', false),
                };
            })
            ->orderBy($table->sort, $table->direction)
            ->orderBy('id')
            ->paginate($table->perPage);

        $instanceCodes = $lengthAwarePaginator->getCollection()->pluck('code');
        $monitoringCounts = Monitoring::query()
            ->withoutGlobalScope('user')
            ->selectRaw('preferred_location, count(*) as monitorings_count')
            ->whereIn('preferred_location', $instanceCodes)
            ->groupBy('preferred_location')
            ->pluck('monitorings_count', 'preferred_location');
        $monitoringTypeCounts = Monitoring::query()
            ->withoutGlobalScope('user')
            ->selectRaw('preferred_location, type, count(*) as monitorings_count')
            ->whereIn('preferred_location', $instanceCodes)
            ->groupBy('preferred_location', 'type')
            ->get()
            ->groupBy('preferred_location')
            ->map(fn (Collection $rows): Collection => $rows->mapWithKeys(
                fn (Monitoring $monitoring): array => [
                    (string) $monitoring->type->value => (int) $monitoring->getAttribute('monitorings_count'),
                ]
            ));

        if ($request->expectsJson()) {
            return AsyncTable::json(
                $lengthAwarePaginator,
                'admin.server-instances.partials.rows',
                [
                    'instances' => $lengthAwarePaginator,
                    'monitoringCounts' => $monitoringCounts,
                    'monitoringTypeCounts' => $monitoringTypeCounts,
                ]
            );
        }

        $summaryInstances = ServerInstance::query()->get();
        $summaryInstanceCodes = $summaryInstances->pluck('code');
        $summaryMonitoringCounts = Monitoring::query()
            ->withoutGlobalScope('user')
            ->selectRaw('preferred_location, count(*) as monitorings_count')
            ->whereIn('preferred_location', $summaryInstanceCodes)
            ->groupBy('preferred_location')
            ->pluck('monitorings_count', 'preferred_location');
        $healthCounts = $summaryInstances
            ->map(fn (ServerInstance $serverInstance): string => $serverInstance->healthStatus())
            ->countBy();

        return view('admin.server-instances.index', [
            'instances' => $lengthAwarePaginator,
            'monitoringCounts' => $monitoringCounts,
            'monitoringTypeCounts' => $monitoringTypeCounts,
            'filters' => [
                [
                    'name' => 'is_active',
                    'label' => __('admin.server_instances.fields.status'),
                    'placeholder' => __('search.filter.text', ['attribute' => __('admin.server_instances.fields.status')]),
                    'options' => [
                        '1' => __('admin.server_instances.fields.active'),
                        '0' => __('admin.server_instances.fields.inactive'),
                    ],
                ],
                [
                    'name' => 'health',
                    'label' => __('admin.server_instances.fields.health'),
                    'placeholder' => __('search.filter.text', ['attribute' => __('admin.server_instances.fields.health')]),
                    'options' => [
                        'healthy' => __('admin.server_instances.health.healthy'),
                        'stale' => __('admin.server_instances.health.stale'),
                        'never_seen' => __('admin.server_instances.health.never_seen'),
                        'inactive' => __('admin.server_instances.health.inactive'),
                    ],
                ],
            ],
            'activeFilters' => [
                'is_active' => (string) ($validated['is_active'] ?? ''),
                'health' => (string) ($validated['health'] ?? ''),
            ],
            'sort' => $table->sort,
            'direction' => $table->direction,
            'summary' => [
                'total_instances' => $summaryInstances->count(),
                'active_instances' => $summaryInstances->where('is_active', true)->count(),
                'stale_instances' => (int) $healthCounts->get('stale', 0),
                'total_monitorings' => (int) $summaryMonitoringCounts->sum(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.server-instances.create');
    }

    public function store(StoreServerInstanceRequest $storeServerInstanceRequest): RedirectResponse
    {
        $validated = $storeServerInstanceRequest->validated();

        ServerInstance::query()->create([
            'code' => $validated['code'],
            'ip_address' => $validated['ip_address'],
            'api_key_hash' => $validated['api_key'],
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return to_route('admin.server-instances.index')->with('success', __('admin.server_instances.messages.instance_created'));
    }

    public function edit(ServerInstance $serverInstance): View
    {
        return view('admin.server-instances.edit', ['instance' => $serverInstance]);
    }

    public function update(UpdateServerInstanceRequest $updateServerInstanceRequest, ServerInstance $serverInstance): RedirectResponse
    {
        $validated = $updateServerInstanceRequest->validated();

        $data = [
            'code' => $validated['code'],
            'ip_address' => $validated['ip_address'],
            'is_active' => $validated['is_active'] ?? false,
        ];

        if (! empty($validated['api_key'])) {
            $data['api_key_hash'] = $validated['api_key'];
        }

        $serverInstance->update($data);

        return to_route('admin.server-instances.index')->with('success', __('admin.server_instances.messages.instance_updated'));
    }

    public function destroy(ServerInstance $serverInstance): RedirectResponse
    {
        if ($serverInstance->monitorings()->exists()) {
            return to_route('admin.server-instances.index')->with('error', __('admin.server_instances.messages.instance_in_use'));
        }

        $serverInstance->delete();

        return to_route('admin.server-instances.index')->with('success', __('admin.server_instances.messages.instance_deleted'));
    }
}
