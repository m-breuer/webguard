<?php

declare(strict_types=1);

namespace Tests\Unit\Support\ActivityLog;

use App\Models\Monitoring;
use App\Support\ActivityLog\RedactActivityLogChanges;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Spatie\Activitylog\Models\Activity;

class RedactActivityLogChangesTest extends TestCase
{
    public function test_updated_activity_backfills_missing_old_attributes_before_redaction(): void
    {
        $monitoring = new Monitoring();
        $monitoring->setRawAttributes([
            'name' => 'Renamed Sensitive HTTP Monitor',
            'auth_password' => 'fresh-basic-password',
        ], true);

        $reflection = new ReflectionClass($monitoring);
        $previous = $reflection->getProperty('previous');
        $previous->setValue($monitoring, [
            'name' => 'Sensitive HTTP Monitor',
            'auth_password' => 'raw-basic-password',
        ]);

        $activity = new Activity();
        $activity->event = 'updated';
        $activity->setRelation('subject', $monitoring);
        $activity->attribute_changes = collect([
            'attributes' => [
                'name' => 'Renamed Sensitive HTTP Monitor',
                'auth_password' => 'fresh-basic-password',
            ],
        ]);

        $action = new RedactActivityLogChanges();
        $method = new ReflectionMethod($action, 'transformChanges');
        $method->invoke($action, $activity);

        $changes = $activity->attribute_changes->toArray();

        $this->assertSame('Sensitive HTTP Monitor', data_get($changes, 'old.name'));
        $this->assertSame('[redacted]', data_get($changes, 'old.auth_password'));
        $this->assertSame('[redacted]', data_get($changes, 'attributes.auth_password'));
    }
}
