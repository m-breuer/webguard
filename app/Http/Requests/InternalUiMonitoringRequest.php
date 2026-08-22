<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\MonitoringType;

class InternalUiMonitoringRequest extends MonitoringRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (in_array($this->input('type'), [MonitoringType::HTTP->value, MonitoringType::KEYWORD->value], true)
            && $this->input('http_headers') === null) {
            $this->merge(['http_headers' => []]);
        }
    }
}
