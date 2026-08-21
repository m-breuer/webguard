<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Monitoring;
use App\Models\StatusPage;

class PublicStatusResourceResolver
{
    public function resolve(string $id): Monitoring|StatusPage
    {
        $statusPage = StatusPage::query()->find($id);

        if ($statusPage instanceof StatusPage) {
            return $statusPage;
        }

        $monitoring = Monitoring::query()->find($id);

        abort_unless($monitoring instanceof Monitoring, 404);

        return $monitoring;
    }
}
