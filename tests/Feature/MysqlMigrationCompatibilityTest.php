<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class MysqlMigrationCompatibilityTest extends TestCase
{
    public function test_notification_delivery_foreign_key_name_fits_mysql_identifier_limit(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_03_26_120000_create_notification_channel_deliveries_table.php'));
        $foreignKeyName = 'notif_channel_deliveries_monitoring_notification_fk';

        $this->assertIsString($migration);
        $this->assertLessThanOrEqual(64, strlen($foreignKeyName));
        $this->assertStringContainsString("->foreign('monitoring_notification_id', '{$foreignKeyName}')", $migration);
    }
}
