<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Tests\TestCase;

class MonitoringFormPresentationTest extends TestCase
{
    public function test_create_and_edit_forms_share_section_order_and_action_placement(): void
    {
        $user = User::factory()->create([
            'package_id' => Package::factory()->create(['monitoring_limit' => 10])->id,
        ]);
        $serverInstance = ServerInstance::query()->firstOrCreate(
            ['code' => 'de-1'],
            ['api_key_hash' => 'test-token-1234567890', 'is_active' => true]
        );
        $monitoring = Monitoring::factory()->for($user)->create([
            'preferred_location' => $serverInstance->code,
        ]);
        $sections = [
            __('monitoring.form.sections.basic'),
            __('monitoring.form.sections.organization'),
            __('monitoring.form.sections.check'),
            __('monitoring.form.sections.sharing'),
            __('monitoring.form.sections.notifications'),
            __('monitoring.form.sections.operations'),
        ];

        $testResponse = $this->actingAs($user)->get(route('monitorings.create'));
        $editResponse = $this->actingAs($user)->get(route('monitorings.edit', $monitoring));

        $testResponse->assertOk()->assertSeeInOrder($sections);
        $editResponse->assertOk()->assertSeeInOrder($sections);
        $testResponse->assertSeeHtml('class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8"');
        $editResponse->assertSeeHtml('class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8"');
        $testResponse->assertSeeHtml('href="' . route('monitorings.index') . '"');
        $editResponse->assertSeeHtml('href="' . route('monitorings.show', $monitoring) . '"');
        $testResponse->assertSeeHtml('<details class="space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">');
        $testResponse->assertSeeHtml('group-open:rotate-180');
        $testResponse->assertSee(__('monitoring.form.advanced_request_settings'));
    }

    public function test_detail_context_and_ownership_actions_are_present_on_their_respective_pages(): void
    {
        $user = User::factory()->create([
            'package_id' => Package::factory()->create(['monitoring_limit' => 10])->id,
        ]);
        $serverInstance = ServerInstance::query()->firstOrCreate(
            ['code' => 'de-1'],
            ['api_key_hash' => 'test-token-1234567890', 'is_active' => true]
        );
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Checkout API',
            'preferred_location' => $serverInstance->code,
        ]);
        $monitoringGroup = MonitoringGroup::factory()->for($user)->create(['name' => 'Production']);
        $monitoring->groups()->attach($monitoringGroup);

        $testResponse = $this->actingAs($user)->get(route('monitorings.show', $monitoring));
        $editResponse = $this->actingAs($user)->get(route('monitorings.edit', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('monitoring.detail.context.ownership'));
        $testResponse->assertSeeText(__('monitoring.detail.context.private'));
        $testResponse->assertSeeText('Production');
        $testResponse->assertDontSeeHtml('action="' . route('monitorings.team-ownership.store', $monitoring) . '"');

        $editResponse->assertOk();
        $editResponse->assertSeeText(__('team.ownership.private'));
        $editResponse->assertSeeText('Production');
        $editResponse->assertSeeHtml('data-monitoring-ownership-status');
        $editResponse->assertSeeHtml('data-monitoring-ownership-badge');
        $editResponse->assertSeeHtml('data-monitoring-form-actions');
        $editResponse->assertSeeHtml('data-monitoring-notification-preferences');
        $editResponse->assertSeeHtml('id="edit-notification-preferences-form"');
        $editResponse->assertSeeHtml('form="edit-notification-preferences-form"');
        $editResponse->assertSeeInOrder([
            __('monitoring.form.sections.notifications'),
            __('team.sections.notification_preferences'),
            'data-monitoring-form-actions',
        ]);
        $editResponse->assertSeeHtml('data-select-control="native"');
        $editResponse->assertSeeHtml('data-select-control="multi"');
    }
}
