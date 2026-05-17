<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MonitoringResultServiceUsageTest extends TestCase
{
    public function test_monitoring_result_service_facade_stays_removed_from_app_code(): void
    {
        $staticCallers = collect(File::allFiles(app_path()))
            ->filter(static fn ($file): bool => str_contains($file->getContents(), 'MonitoringResultService::'))
            ->map(static fn ($file): string => $file->getRelativePathname())
            ->values()
            ->all();

        $this->assertFalse(File::exists(app_path('Services/MonitoringResultService.php')));
        $this->assertSame([], $staticCallers);
    }
}
