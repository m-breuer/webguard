<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MonitoringStatus;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use Illuminate\Http\JsonResponse;

class HeartbeatPingController extends Controller
{
    public function __invoke(string $token): JsonResponse
    {
        $monitoring = Monitoring::query()
            ->where('type', 'heartbeat')
            ->where('heartbeat_token', $token)
            ->firstOrFail();

        $timestamp = now();

        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'http_status_code' => 200,
            'response_time' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $monitoring->forceFill([
            'heartbeat_last_ping_at' => $timestamp,
        ])->save();

        return response()->json([
            'message' => 'Heartbeat accepted.',
        ]);
    }
}
