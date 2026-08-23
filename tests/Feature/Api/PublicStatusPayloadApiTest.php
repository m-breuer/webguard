<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\MonitoringStatus;
use App\Mail\PublicStatusPageSubscriptionConfirmationMail;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringResponse;
use App\Models\Package;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicStatusPayloadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_status_page_payload_contains_only_public_status_data(): void
    {
        Date::setTestNow('2026-08-23 10:00:00');
        $user = $this->user();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Checkout API',
            'target' => 'https://internal.example.test/private-path',
        ]);
        MonitoringResponse::query()->create([
            'monitoring_id' => $monitoring->id,
            'status' => MonitoringStatus::UP,
            'created_at' => Date::now()->subMinutes(2),
            'updated_at' => Date::now()->subMinutes(2),
        ]);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'description' => 'Customer-facing service health.',
            'is_public' => true,
        ]);
        $statusPage->components()->create(['name' => 'Core services', 'position' => 0])
            ->monitorings()
            ->attach($monitoring->id, ['position' => 0]);
        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::now()->subDay(),
            'up_at' => Date::now()->subHours(23),
        ]);

        $this->getJson(route('public.status.show', $statusPage))
            ->assertOk()
            ->assertHeader('Cache-Control')
            ->assertJsonPath('data.kind', 'status_page')
            ->assertJsonPath('data.identifier', $statusPage->id)
            ->assertJsonPath('data.components.0.monitorings.0.name', 'Checkout API')
            ->assertJsonPath('data.incidents.0.monitoring_name', 'Checkout API')
            ->assertJsonMissing(['target' => 'https://internal.example.test/private-path']);

        $this->getJson(route('public.status.show', ['status' => 'acme-status']))
            ->assertOk()
            ->assertJsonPath('data.identifier', $statusPage->id);
    }

    public function test_private_public_status_resources_are_not_exposed(): void
    {
        $user = $this->user();
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Private status',
            'is_public' => false,
        ]);
        $monitoring = Monitoring::factory()->for($user)->create(['public_label_enabled' => false]);

        $this->getJson(route('public.status.show', $statusPage))->assertNotFound();
        $this->getJson(route('public.status.show', $monitoring))->assertNotFound();
    }

    public function test_public_monitoring_payload_keeps_label_details_and_hides_heartbeat_targets(): void
    {
        $user = $this->user();
        $monitoring = Monitoring::factory()->for($user)->create([
            'name' => 'Public API',
            'public_label_enabled' => true,
            'target' => 'https://status.example.test/api',
        ]);
        $heartbeat = Monitoring::factory()->heartbeat()->for($user)->create([
            'public_label_enabled' => true,
        ]);

        $this->getJson(route('public.status.show', $monitoring))
            ->assertOk()
            ->assertJsonPath('data.kind', 'monitoring')
            ->assertJsonPath('data.monitoring.target', 'https://status.example.test/api');

        $this->getJson(route('public.status.show', $heartbeat))
            ->assertOk()
            ->assertJsonPath('data.monitoring.target', null);
    }

    public function test_public_status_page_subscription_endpoint_sends_confirmation_without_authentication(): void
    {
        Mail::fake();
        $user = $this->user();
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'is_public' => true,
        ]);

        $this->postJson(route('public.status.subscribers.store', $statusPage), ['email' => 'Customer@Example.test'])
            ->assertStatus(202)
            ->assertJsonPath('data.message', 'Check your inbox to confirm your subscription.');

        $this->assertDatabaseHas('status_page_subscriptions', [
            'status_page_id' => $statusPage->id,
            'email' => 'customer@example.test',
        ]);
        Mail::assertSent(PublicStatusPageSubscriptionConfirmationMail::class);
    }

    private function user(): User
    {
        return User::factory()->create(['package_id' => Package::factory()->create(['monitoring_limit' => 20])->id]);
    }
}
