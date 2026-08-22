<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Enums\MonitoringType;
use App\Http\Controllers\Controller;
use App\Models\Monitoring;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringFormOptionsController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['data' => $this->payload($user)]);
    }

    public function edit(Request $request, Monitoring $monitoring): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($monitoring->isManageableBy($user) && ! $user->isDemo(), 403);

        return response()->json(['data' => $this->payload($user, $monitoring->loadMissing('groups'))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(User $user, ?Monitoring $monitoring = null): array
    {
        $builder = ServerInstance::query();

        if ($monitoring !== null) {
            $builder->where(function ($query) use ($monitoring): void {
                $query->where('is_active', true)
                    ->orWhereIn('code', $monitoring->preferredLocationCodes());
            });
        } else {
            $builder->active();
        }

        return [
            'types' => array_map(static fn (MonitoringType $monitoringType): string => $monitoringType->value, MonitoringType::cases()),
            'locations' => $builder->orderBy('code')->pluck('code')->values()->all(),
            'groups' => $user->monitoringGroups()->orderBy('name')->get(['id', 'name'])->map(
                static fn ($group): array => ['id' => $group->id, 'name' => $group->name]
            )->values()->all(),
            'teams' => $user->administeredTeams()->orderBy('name')->get(['teams.id', 'teams.name'])->map(
                static fn ($team): array => ['id' => $team->id, 'name' => $team->name]
            )->values()->all(),
            'notification_channels' => $user->enabledNotificationChannelKeys(),
            'monitoring' => $monitoring === null ? null : $this->configuration($monitoring),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function configuration(Monitoring $monitoring): array
    {
        return [
            'id' => $monitoring->id,
            'name' => $monitoring->name,
            'type' => $monitoring->type->value,
            'target' => $monitoring->target,
            'status' => $monitoring->status->value,
            'port' => $monitoring->port,
            'keyword' => $monitoring->keyword,
            'dns_record_type' => $monitoring->dns_record_type,
            'dns_expected_values' => $monitoring->dns_expected_values,
            'timeout' => $monitoring->timeout,
            'http_method' => $monitoring->http_method?->value,
            'expected_http_statuses' => $monitoring->expected_http_statuses,
            'preferred_locations' => $monitoring->preferredLocationCodes(),
            'group_ids' => $monitoring->groups->pluck('id')->values()->all(),
            'can_assign_groups' => $monitoring->isPrivateOwned(),
            'notification_on_failure' => $monitoring->notification_on_failure,
            'notification_channels' => $monitoring->notification_channels,
            'failure_confirmation_threshold' => $monitoring->failure_confirmation_threshold,
            'ssl_expiry_warning_days' => $monitoring->ssl_expiry_warning_days,
            'heartbeat_interval_minutes' => $monitoring->heartbeat_interval_minutes,
            'heartbeat_grace_minutes' => $monitoring->heartbeat_grace_minutes,
            'server_health_cpu_threshold_percent' => $monitoring->server_health_cpu_threshold_percent,
            'server_health_ram_threshold_percent' => $monitoring->server_health_ram_threshold_percent,
            'server_health_storage_threshold_percent' => $monitoring->server_health_storage_threshold_percent,
            'server_health_report_interval_minutes' => $monitoring->server_health_report_interval_minutes,
            'server_health_grace_minutes' => $monitoring->server_health_grace_minutes,
        ];
    }
}
