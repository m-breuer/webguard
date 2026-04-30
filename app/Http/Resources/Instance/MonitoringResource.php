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
            'expected_http_statuses' => $this->expected_http_statuses,
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
            'heartbeat_interval_minutes' => $this->heartbeat_interval_minutes,
            'heartbeat_grace_minutes' => $this->heartbeat_grace_minutes,
            'heartbeat_last_ping_at' => $this->heartbeat_last_ping_at,
            'domain_expires_at' => $this->whenLoaded('domainResult', fn () => $this->domainResult?->expires_at),
            'domain_registrar' => $this->whenLoaded('domainResult', fn () => $this->domainResult?->registrar),
            'latest_http_status_code' => $this->whenLoaded('latestResponseResult', fn () => $this->latestResponseResult?->http_status_code),
        ];
    }
}
