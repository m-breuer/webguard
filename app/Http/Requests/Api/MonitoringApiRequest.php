<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Models\Monitoring;
use Illuminate\Foundation\Http\FormRequest;

abstract class MonitoringApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $monitoring = $this->route('monitoring');

        if (! $monitoring instanceof Monitoring) {
            return true;
        }

        $user = $this->user();

        if ($user && $monitoring->isVisibleTo($user)) {
            return true;
        }

        abort_unless($monitoring->public_label_enabled, 404);

        return true;
    }
}
