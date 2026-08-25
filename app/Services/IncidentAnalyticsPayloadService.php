<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\IncidentCustomerImpact;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use App\Enums\MonitoringStatus;
use App\Enums\StatusPageComponentSource;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use App\Models\StatusPage;
use App\Models\StatusPageComponent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

final class IncidentAnalyticsPayloadService
{
    public function __construct(
        private readonly MonitoringOverviewService $monitoringOverviewService,
        private readonly MonitoringStatusService $monitoringStatusService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{data:array<string, mixed>,pagination:array{current_page:int,last_page:int,total:int,from:int|null,to:int|null}}
     */
    public function for(User $user, array $filters, int $page = 1): array
    {
        $days = (int) ($filters['days'] ?? 90);
        $incidents = $this->incidents($user, $filters, $days);
        $incidentPaginator = $this->incidentPaginator($user, $filters, $days, $page);
        $resolvedIncidents = $incidents->filter(static fn (Incident $incident): bool => $incident->up_at !== null);
        $durations = $resolvedIncidents->map(
            static fn (Incident $incident): int => (int) $incident->down_at->diffInMinutes($incident->up_at)
        );
        $byService = $this->groupCounts($incidents, static fn (Incident $incident): string => $incident->affected_service ?: $incident->monitoring->name);
        $overview = $this->monitoringOverviewService->overview($user);

        return [
            'data' => [
                'overview' => [
                    'overall_state' => $overview['overallState'],
                    'summary' => $overview['summary'],
                ],
                'groups' => $this->monitoringGroups($user)->map(
                    fn (array $group): array => $this->monitoringGroupPayload($group)
                )->values()->all(),
                'status_pages' => $this->statusPages($user)->map(
                    fn (array $statusPage): array => $this->statusPagePayload($statusPage)
                )->values()->all(),
                'filters' => [
                    'days' => $days,
                    'incident_type' => $filters['incident_type'] ?? null,
                    'severity' => $filters['severity'] ?? null,
                    'customer_impact' => $filters['customer_impact'] ?? null,
                    'affected_service' => $filters['affected_service'] ?? null,
                ],
                'filter_options' => [
                    'incident_types' => $this->enumOptions(IncidentType::cases(), 'incidents.types.'),
                    'severities' => $this->enumOptions(IncidentSeverity::cases(), 'incidents.severities.'),
                    'customer_impacts' => $this->enumOptions(IncidentCustomerImpact::cases(), 'incidents.customer_impacts.'),
                ],
                'metrics' => [
                    'total' => $incidents->count(),
                    'resolved' => $resolvedIncidents->count(),
                    'open' => $incidents->count() - $resolvedIncidents->count(),
                    'mttr_minutes' => $durations->isEmpty() ? null : (int) round($durations->avg()),
                ],
                'trend' => $this->incidentTrend($incidents, $days),
                'repeat_services' => $byService->filter(static fn (int $count): bool => $count > 1)
                    ->take(5)
                    ->map(static fn (int $count, string $service): array => ['service' => $service, 'count' => $count])
                    ->values()
                    ->all(),
                'distributions' => [
                    'by_type' => $this->distribution($this->groupCounts($incidents, static fn (Incident $incident): string => $incident->incident_type?->value ?? 'unclassified'), 'incidents.types.'),
                    'by_severity' => $this->distribution($this->groupCounts($incidents, static fn (Incident $incident): string => $incident->severity?->value ?? 'unclassified'), 'incidents.severities.'),
                    'by_impact' => $this->distribution($this->groupCounts($incidents, static fn (Incident $incident): string => $incident->customer_impact?->value ?? 'unclassified'), 'incidents.customer_impacts.'),
                ],
                'incidents' => $incidentPaginator->getCollection()->map(
                    fn (Incident $incident): array => $this->incidentPayload($incident)
                )->values()->all(),
            ],
            'pagination' => [
                'current_page' => $incidentPaginator->currentPage(),
                'last_page' => $incidentPaginator->lastPage(),
                'total' => $incidentPaginator->total(),
                'from' => $incidentPaginator->firstItem(),
                'to' => $incidentPaginator->lastItem(),
            ],
        ];
    }

    /**
     * @return Collection<int, array{model:MonitoringGroup,summary:array{total:int,healthy:int,down:int,attention:int,state:string}}>
     */
    private function monitoringGroups(User $user): Collection
    {
        return $user->monitoringGroups()
            ->withCount('monitorings')
            ->with(['monitorings.latestResponseResult', 'monitorings.latestIncident'])
            ->orderBy('name')
            ->get()
            ->map(fn (MonitoringGroup $monitoringGroup): array => [
                'model' => $monitoringGroup,
                'summary' => $this->summarizeMonitorings($monitoringGroup->monitorings),
            ]);
    }

    /**
     * @return Collection<int, array{model:StatusPage,summary:array{total:int,healthy:int,down:int,attention:int,state:string}}>
     */
    private function statusPages(User $user): Collection
    {
        return $user->statusPages()
            ->withCount('components')
            ->with([
                'components.monitorings.latestResponseResult',
                'components.monitorings.latestIncident',
                'components.monitoringGroup.monitorings.latestResponseResult',
                'components.monitoringGroup.monitorings.latestIncident',
            ])
            ->latest()
            ->get()
            ->map(fn (StatusPage $statusPage): array => [
                'model' => $statusPage,
                'summary' => $this->summarizeMonitorings($this->statusPageMonitorings($statusPage)),
            ]);
    }

    /**
     * @param  array{model:MonitoringGroup,summary:array{total:int,healthy:int,down:int,attention:int,state:string}}  $group
     * @return array<string, int|string>
     */
    private function monitoringGroupPayload(array $group): array
    {
        /** @var MonitoringGroup $monitoringGroup */
        $monitoringGroup = $group['model'];

        return [
            'id' => (string) $monitoringGroup->getKey(),
            'name' => $monitoringGroup->name,
            'monitoring_count' => $group['summary']['total'],
            ...$group['summary'],
        ];
    }

    /**
     * @param  array{model:StatusPage,summary:array{total:int,healthy:int,down:int,attention:int,state:string}}  $statusPageData
     * @return array<string, bool|int|string>
     */
    private function statusPagePayload(array $statusPageData): array
    {
        /** @var StatusPage $statusPage */
        $statusPage = $statusPageData['model'];

        return [
            'id' => (string) $statusPage->getKey(),
            'name' => $statusPage->name,
            'is_public' => $statusPage->is_public,
            'component_count' => $statusPage->components_count,
            ...$statusPageData['summary'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return EloquentCollection<int, Incident>
     */
    private function incidents(User $user, array $filters, int $days): EloquentCollection
    {
        return $this->incidentQuery($user, $filters, $days)->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Incident>
     */
    private function incidentPaginator(User $user, array $filters, int $days, int $page): LengthAwarePaginator
    {
        return $this->incidentQuery($user, $filters, $days)->paginate(10, ['*'], 'page', $page);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Incident>
     */
    private function incidentQuery(User $user, array $filters, int $days): Builder
    {
        $builder = Incident::query()
            ->whereHas('monitoring', fn (Builder $builder): Builder => $builder->visibleTo($user))
            ->with('monitoring')
            ->whereBetween('down_at', [Date::now()->subDays($days)->startOfDay(), Date::now()->endOfDay()])
            ->latest('down_at');

        foreach (['incident_type', 'severity', 'customer_impact'] as $filter) {
            if (! empty($filters[$filter])) {
                $builder->where($filter, $filters[$filter]);
            }
        }

        if (! empty($filters['affected_service'])) {
            $builder->where('affected_service', 'like', '%' . $filters['affected_service'] . '%');
        }

        return $builder;
    }

    /**
     * @param  EloquentCollection<int, Incident>  $eloquentCollection
     * @return Collection<string, int>
     */
    private function groupCounts(EloquentCollection $eloquentCollection, callable $keyResolver): Collection
    {
        return $eloquentCollection->groupBy($keyResolver)
            ->map(static fn (Collection $group): int => $group->count())
            ->sortDesc();
    }

    /**
     * @param  Collection<int, Monitoring>  $monitorings
     * @return array{total:int,healthy:int,down:int,attention:int,state:string}
     */
    private function summarizeMonitorings(Collection $monitorings): array
    {
        $states = $monitorings->map(fn (Monitoring $monitoring): string => $this->monitoringState($monitoring));
        $down = $states->filter(static fn (string $state): bool => $state === MonitoringStatus::DOWN->value)->count();
        $healthy = $states->filter(static fn (string $state): bool => $state === MonitoringStatus::UP->value)->count();
        $attention = $states->filter(static fn (string $state): bool => in_array($state, ['unknown', 'paused', 'maintenance'], true))->count();

        return [
            'total' => $states->count(),
            'healthy' => $healthy,
            'down' => $down,
            'attention' => $attention,
            'state' => $states->isEmpty() ? 'new' : ($down > 0 ? 'degraded' : ($attention > 0 ? 'attention' : 'healthy')),
        ];
    }

    private function monitoringState(Monitoring $monitoring): string
    {
        if ($monitoring->isPaused()) {
            return 'paused';
        }

        if ($monitoring->isUnderMaintenance()) {
            return 'maintenance';
        }

        if ($monitoring->latestIncident !== null && $monitoring->latestIncident->up_at === null) {
            return MonitoringStatus::DOWN->value;
        }

        $status = $this->monitoringStatusService->getStatusNow($monitoring)['status'];

        return $status instanceof MonitoringStatus ? $status->value : (string) $status;
    }

    /**
     * @return Collection<int, Monitoring>
     */
    private function statusPageMonitorings(StatusPage $statusPage): Collection
    {
        return $statusPage->components
            ->flatMap(static function (StatusPageComponent $statusPageComponent): Collection {
                if ($statusPageComponent->source_type === StatusPageComponentSource::MONITORING_GROUP) {
                    return $statusPageComponent->monitoringGroup?->monitorings ?? collect();
                }

                return $statusPageComponent->monitorings;
            })
            ->unique('id')
            ->values();
    }

    /**
     * @param  EloquentCollection<int, Incident>  $eloquentCollection
     * @return array{points:list<array{label:string,count:int,x:float,y:float}>,max:int}
     */
    private function incidentTrend(EloquentCollection $eloquentCollection, int $days): array
    {
        $pointCount = $days >= 90 ? 10 : 7;
        $bucketDays = max(1, (int) ceil($days / $pointCount));
        $periodStart = Date::now()->subDays($days - 1)->startOfDay();
        $now = Date::now();
        $points = collect();

        for ($index = 0; $index < $pointCount; $index++) {
            $bucketStart = $periodStart->copy()->addDays($index * $bucketDays);

            if ($bucketStart->isAfter($now)) {
                break;
            }

            $bucketEnd = $bucketStart->copy()->addDays($bucketDays - 1)->endOfDay();
            $bucketEnd = $bucketEnd->isAfter($now) ? $now : $bucketEnd;
            $count = $eloquentCollection->filter(static fn (Incident $incident): bool => $incident->down_at->betweenIncluded($bucketStart, $bucketEnd))->count();

            $points->push([
                'label' => $bucketStart->locale(app()->getLocale())->isoFormat('D. MMM'),
                'count' => $count,
            ]);
        }

        $max = max(1, (int) $points->max('count'));
        $lastIndex = max(1, $points->count() - 1);

        return [
            'points' => $points->values()->map(static fn (array $point, int $index): array => [
                ...$point,
                'x' => round(($index / $lastIndex) * 100, 2),
                'y' => round(78 - (($point['count'] / $max) * 58), 2),
            ])->all(),
            'max' => $max,
        ];
    }

    /**
     * @param  list<IncidentType|IncidentSeverity|IncidentCustomerImpact>  $cases
     * @return list<array{value:string,label:string}>
     */
    private function enumOptions(array $cases, string $translationPrefix): array
    {
        return array_map(
            static fn (IncidentType|IncidentSeverity|IncidentCustomerImpact $case): array => [
                'value' => $case->value,
                'label' => __($translationPrefix . $case->value),
            ],
            $cases,
        );
    }

    /**
     * @param  Collection<string, int>  $counts
     * @return list<array{key:string,label:string,count:int}>
     */
    private function distribution(Collection $counts, string $translationPrefix): array
    {
        return $counts->map(
            static fn (int $count, string $key): array => [
                'key' => $key,
                'label' => __($translationPrefix . $key),
                'count' => $count,
            ]
        )->values()->all();
    }

    /**
     * @return array<string, int|string|null>
     */
    private function incidentPayload(Incident $incident): array
    {
        $durationMinutes = $incident->up_at === null ? null : (int) $incident->down_at->diffInMinutes($incident->up_at);

        return [
            'id' => (string) $incident->getKey(),
            'monitoring_id' => (string) $incident->monitoring->getKey(),
            'monitoring_name' => $incident->monitoring->name,
            'affected_service' => $incident->affected_service ?: $incident->monitoring->name,
            'status' => $incident->up_at === null ? 'open' : 'resolved',
            'incident_type' => $incident->incident_type?->value ?? 'unclassified',
            'severity' => $incident->severity?->value ?? 'unclassified',
            'customer_impact' => $incident->customer_impact?->value ?? 'unclassified',
            'problem_description' => $incident->problem_description,
            'down_at' => $incident->down_at->toIso8601String(),
            'up_at' => $incident->up_at?->toIso8601String(),
            'duration_minutes' => $durationMinutes,
        ];
    }
}
