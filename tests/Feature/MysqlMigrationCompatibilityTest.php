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
        $this->assertLessThanOrEqual(64, mb_strlen($foreignKeyName));
        $this->assertStringContainsString("->foreign('monitoring_notification_id', '{$foreignKeyName}')", $migration);
    }

    public function test_status_page_component_pivot_foreign_key_names_fit_mysql_identifier_limit(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_05_14_132000_create_status_page_component_monitoring_table.php'));
        $componentForeignKeyName = 'status_page_component_monitoring_component_fk';
        $monitoringForeignKeyName = 'status_page_component_monitoring_monitoring_fk';

        $this->assertIsString($migration);
        $this->assertLessThanOrEqual(64, mb_strlen($componentForeignKeyName));
        $this->assertLessThanOrEqual(64, mb_strlen($monitoringForeignKeyName));
        $this->assertStringContainsString("->foreign('status_page_component_id', '{$componentForeignKeyName}')", $migration);
        $this->assertStringContainsString("->foreign('monitoring_id', '{$monitoringForeignKeyName}')", $migration);
    }
}
