<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MonitoringType;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Tests\TestCase;

class MonitoringSlaBadgeEmbedTest extends TestCase
{
    public function test_edit_page_explains_and_shows_sla_badge_embed_snippet_when_public_label_is_enabled(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        ServerInstance::query()->firstOrCreate(
            ['code' => 'de-1'],
            ['name' => 'Germany 1', 'api_key_hash' => 'test-token-1234567890', 'is_active' => true]
        );
        $monitoring = Monitoring::factory()->for($user)->create([
            'type' => MonitoringType::HTTP,
            'target' => 'https://example.com',
            'public_label_enabled' => true,
        ]);

        $testResponse = $this->actingAs($user)->get(route('monitorings.edit', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('monitoring.detail.sla_badge.heading'));
        $testResponse->assertSeeText(__('monitoring.detail.sla_badge.description'));
        $testResponse->assertSeeText(__('monitoring.detail.sla_badge.snippet_help'));
        $testResponse->assertSeeHtml('id="sla-badge-snippet"');
        $testResponse->assertSeeHtml('data-webguard-sla-badge');
        $testResponse->assertSeeHtml('data-range="90"');
        $testResponse->assertSeeHtml(route('badge.js'));
    }
}
