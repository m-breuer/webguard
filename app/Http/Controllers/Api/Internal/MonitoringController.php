<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal;

use App\Enums\MonitoringStatus;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\MonitoringSslResult;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MonitoringController extends Controller
{
    public function storeResponse(Request $request)
    {
        $validated = $request->validate([
            'monitoring_id' => ['required', 'exists:monitorings,id'],
            'status' => ['required', Rule::enum(MonitoringStatus::class)],
            'response_time' => ['nullable', 'numeric', 'min:0'],
        ]);

        MonitoringResponse::query()->create($validated);

        return response()->json(['message' => 'Monitoring response stored successfully.']);
    }

    public function storeIncident(Request $request)
    {
        $validated = $request->validate([
            'monitoring_id' => ['required', 'exists:monitorings,id'],
            'down_at' => ['required', 'date'],
        ]);

        Incident::query()->firstOrCreate(['monitoring_id' => $validated['monitoring_id'], 'up_at' => null], $validated);

        return response()->json(['message' => 'Incident stored successfully.']);
    }

    public function updateIncident(Request $request, Monitoring $monitoring)
    {
        $validated = $request->validate([
            'up_at' => ['required', 'date'],
        ]);

        $incident = Incident::query()->where('monitoring_id', $monitoring->id)
            ->whereNull('up_at')
            ->first();

        if (! $incident) {
            return response()->json(['message' => 'No open incident found for this monitoring.'], 404);
        }

        $incident->update($validated);

        return response()->json(['message' => 'Incident updated successfully.']);
    }

    public function storeSsl(Request $request)
    {
        $validated = $request->validate([
            'monitoring_id' => ['required', 'exists:monitorings,id'],
            'is_valid' => ['required', 'boolean'],
            'expires_at' => ['nullable', 'date'],
            'issuer' => ['nullable', 'string'],
            'issued_at' => ['nullable', 'date'],
        ]);

        MonitoringSslResult::query()->updateOrCreate(['monitoring_id' => $validated['monitoring_id']], $validated);

        return response()->json(['message' => 'SSL result stored successfully.']);
    }
}
