<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StatusPageComponentSource;
use App\Http\Requests\StatusPages\StatusPageRequest;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\StatusPage;
use App\Models\StatusPageComponent;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\View\View;

class StatusPageController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        return view('status-pages.index', [
            'statusPages' => $user->statusPages()
                ->withCount('components')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        abort_if(Auth::user()->isDemo(), 403);

        return view('status-pages.create', [
            'monitorings' => $this->monitoringOptions(),
            'defaultComponents' => $this->defaultComponents(),
        ]);
    }

    public function store(StatusPageRequest $statusPageRequest): RedirectResponse
    {
        abort_if(Auth::user()->isDemo(), 403);

        /** @var User $user */
        $user = $statusPageRequest->user();
        $validated = $statusPageRequest->validated();

        $statusPage = $user->statusPages()->create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_public' => $validated['is_public'],
        ]);

        $this->syncComponents($statusPage, $validated['components']);

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('status_page.messages.created'));
    }

    public function show(StatusPage $statusPage): View
    {
        $this->authorizeOwner($statusPage);

        $this->loadStatusPageComponents($statusPage);

        return view('status-pages.show', [
            'statusPage' => $statusPage,
            'incidents' => $this->recentIncidents($statusPage),
        ]);
    }

    public function edit(StatusPage $statusPage): View
    {
        abort_if(Auth::user()->isDemo(), 403);
        $this->authorizeOwner($statusPage);

        $this->loadStatusPageComponents($statusPage);

        return view('status-pages.edit', [
            'statusPage' => $statusPage,
            'monitorings' => $this->monitoringOptions(),
            'defaultComponents' => [],
        ]);
    }

    public function update(StatusPageRequest $statusPageRequest, StatusPage $statusPage): RedirectResponse
    {
        abort_if(Auth::user()->isDemo(), 403);
        $this->authorizeOwner($statusPage);

        $validated = $statusPageRequest->validated();

        $statusPage->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_public' => $validated['is_public'],
        ]);

        $this->syncComponents($statusPage, $validated['components']);

        return to_route('status-pages.show', $statusPage)
            ->with('success', __('status_page.messages.updated'));
    }

    public function destroy(StatusPage $statusPage): RedirectResponse
    {
        abort_if(Auth::user()->isDemo(), 403);
        $this->authorizeOwner($statusPage);

        $statusPage->delete();

        return to_route('status-pages.index')
            ->with('success', __('status_page.messages.deleted'));
    }

    private function authorizeOwner(StatusPage $statusPage): void
    {
        abort_unless($statusPage->user_id === Auth::id(), 404);
    }

    /**
     * @return Collection<int, Monitoring>
     */
    private function monitoringOptions()
    {
        return Monitoring::query()
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'target']);
    }

    /**
     * @param  list<array{name: string, description: string|null, source_type: string, monitoring_group_id: string|null, monitoring_ids: list<string>}>  $components
     */
    private function syncComponents(StatusPage $statusPage, array $components): void
    {
        $statusPage->components()->delete();

        foreach (array_values($components) as $componentPosition => $componentData) {
            $component = $statusPage->components()->create([
                'name' => $componentData['name'],
                'description' => $componentData['description'] ?? null,
                'position' => $componentPosition,
                'source_type' => $componentData['source_type'],
                'monitoring_group_id' => $componentData['source_type'] === StatusPageComponentSource::MONITORING_GROUP->value
                    ? $componentData['monitoring_group_id']
                    : null,
            ]);

            if ($componentData['source_type'] === StatusPageComponentSource::MONITORING_GROUP->value) {
                continue;
            }

            $syncPayload = [];
            foreach (array_values($componentData['monitoring_ids']) as $monitoringPosition => $monitoringId) {
                $syncPayload[$monitoringId] = ['position' => $monitoringPosition];
            }

            $component->monitorings()->sync($syncPayload);
        }
    }

    /**
     * @return list<array{name: string, description: string|null, source_type: string, monitoring_group_id: string|null, monitoring_ids: list<string>}>
     */
    private function defaultComponents(): array
    {
        return [
            ['name' => 'API', 'description' => null, 'source_type' => StatusPageComponentSource::MANUAL->value, 'monitoring_group_id' => null, 'monitoring_ids' => []],
            ['name' => 'Web App', 'description' => null, 'source_type' => StatusPageComponentSource::MANUAL->value, 'monitoring_group_id' => null, 'monitoring_ids' => []],
            ['name' => 'Workers', 'description' => null, 'source_type' => StatusPageComponentSource::MANUAL->value, 'monitoring_group_id' => null, 'monitoring_ids' => []],
            ['name' => 'Database', 'description' => null, 'source_type' => StatusPageComponentSource::MANUAL->value, 'monitoring_group_id' => null, 'monitoring_ids' => []],
        ];
    }

    private function loadStatusPageComponents(StatusPage $statusPage): void
    {
        $statusPage->loadMissing([
            'components.monitorings',
            'components.monitoringGroup.monitorings' => fn ($query) => $query->orderBy('name'),
        ]);
    }

    /**
     * @return Collection<int, Monitoring>
     */
    private function componentMonitorings(StatusPageComponent $statusPageComponent): Collection
    {
        if ($statusPageComponent->source_type === StatusPageComponentSource::MONITORING_GROUP) {
            return $statusPageComponent->monitoringGroup?->monitorings ?? new Collection();
        }

        return $statusPageComponent->monitorings;
    }

    /**
     * @return Collection<int, Incident>
     */
    private function recentIncidents(StatusPage $statusPage): Collection
    {
        $monitoringIds = $statusPage->components
            ->flatMap(fn (StatusPageComponent $statusPageComponent) => $this->componentMonitorings($statusPageComponent)->pluck('id'))
            ->unique()
            ->values();

        if ($monitoringIds->isEmpty()) {
            return new Collection();
        }

        return Incident::query()
            ->with(['monitoring', 'updates'])
            ->whereIn('monitoring_id', $monitoringIds)
            ->whereBetween('down_at', [Date::now()->subDays(90)->startOfDay(), Date::now()->endOfDay()])
            ->latest('down_at')
            ->limit(10)
            ->get();
    }
}
