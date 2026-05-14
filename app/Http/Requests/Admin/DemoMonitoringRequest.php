<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Http\Requests\MonitoringRequest;
use App\Models\User;

class DemoMonitoringRequest extends MonitoringRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function notificationChannelUser(): ?User
    {
        return User::query()
            ->where('role', UserRole::DEMO)
            ->first();
    }
}
