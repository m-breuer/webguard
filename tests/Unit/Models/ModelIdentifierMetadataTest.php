<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ApiLog;
use App\Models\IncidentUpdate;
use App\Models\Monitoring;
use App\Models\MonitoringDomainResult;
use App\Models\MonitoringGroup;
use App\Models\MonitoringNotification;
use App\Models\MonitoringResponse;
use App\Models\MonitoringResponseArchived;
use App\Models\MonitoringSslResult;
use App\Models\NotificationChannelDelivery;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\StatusPage;
use App\Models\StatusPageComponent;
use App\Models\StatusPageSubscriber;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Tests\TestCase;

class ModelIdentifierMetadataTest extends TestCase
{
    /**
     * @return array<string, array{class-string<Model>}>
     */
    public static function stringIdentifierModels(): array
    {
        return [
            'api log' => [ApiLog::class],
            'incident update' => [IncidentUpdate::class],
            'monitoring' => [Monitoring::class],
            'monitoring domain result' => [MonitoringDomainResult::class],
            'monitoring group' => [MonitoringGroup::class],
            'monitoring notification' => [MonitoringNotification::class],
            'monitoring response' => [MonitoringResponse::class],
            'monitoring response archived' => [MonitoringResponseArchived::class],
            'monitoring ssl result' => [MonitoringSslResult::class],
            'notification channel delivery' => [NotificationChannelDelivery::class],
            'package' => [Package::class],
            'server instance' => [ServerInstance::class],
            'status page' => [StatusPage::class],
            'status page component' => [StatusPageComponent::class],
            'status page subscriber' => [StatusPageSubscriber::class],
        ];
    }

    public function test_refactored_models_keep_string_non_incrementing_identifiers(): void
    {
        foreach (self::stringIdentifierModels() as [$modelClass]) {
            $model = new $modelClass;
            $reflectionClass = new ReflectionClass($modelClass);

            $declaresIncrementingProperty = $reflectionClass->hasProperty('incrementing')
                && $reflectionClass->getProperty('incrementing')->getDeclaringClass()->getName() === $modelClass;

            $this->assertFalse($declaresIncrementingProperty);
            $this->assertFalse($model->getIncrementing());
            $this->assertSame('string', $model->getKeyType());
        }
    }
}
