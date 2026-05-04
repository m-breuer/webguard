<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\Package;
use App\Models\User;
use Tests\TestCase;

class ConfirmDialogTest extends TestCase
{
    public function test_monitoring_destructive_actions_use_app_confirm_dialog(): void
    {
        Package::factory()->create();
        $user = User::factory()->create();
        $monitoring = Monitoring::factory()->create(['user_id' => $user->id]);

        $testResponse = $this->actingAs($user)->get(route('monitorings.show', $monitoring));

        $testResponse->assertOk();
        $testResponse->assertSeeHtml('x-data="confirmDialog()"');
        $testResponse->assertSeeHtml('data-confirm-message="' . __('monitoring.actions.reset.confirmation') . '"');
        $testResponse->assertSeeHtml('data-confirm-message="' . __('monitoring.actions.delete.confirmation') . '"');
        $testResponse->assertDontSeeHtml('return confirm(');
    }
}
