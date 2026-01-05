<?php

namespace App\Http\Controllers\Api\Internal;

use App\Enums\MonitoringType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Instance\MonitoringResource;
use App\Models\Monitoring;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MonitoringListController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'location' => ['required', 'string'],
            'type' => ['nullable', 'string', Rule::in(array_column(MonitoringType::cases(), 'value'))],
        ]);

        $location = $validated['location'];
        $type = $validated['type'] ?? null;

        $builder = Monitoring::query()
            ->where('status', 'active')
            ->where('preferred_location', $location);

        if ($type) {
            $builder->where('type', $type);
        }

        $monitorings = $builder->get();

        return MonitoringResource::collection($monitorings);
    }
}
