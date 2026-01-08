<?php

declare(strict_types=1);

namespace App\Http\Resources\Instance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'target' => $this->target,
            'port' => $this->port,
            'keyword' => $this->keyword,
            'status' => $this->status,
            'timeout' => $this->timeout,
            'http_method' => $this->http_method,
            'http_headers' => $this->http_headers,
            'http_body' => $this->http_body,
            'auth_username' => $this->auth_username,
            'auth_password' => $this->auth_password,
            'public_label_enabled' => $this->public_label_enabled,
            'preferred_location' => $this->preferred_location,
            'deleted_at' => $this->deleted_at,
            'maintenance_active' => $this->isUnderMaintenance(),
            'maintenance_from' => $this->maintenance_from,
            'maintenance_until' => $this->maintenance_until,
        ];
    }
}
