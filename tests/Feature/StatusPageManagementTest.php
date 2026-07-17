<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\IncidentUpdateStatus;
use App\Enums\StatusPageComponentSource;
use App\Enums\UserRole;
use App\Mail\PublicStatusPageSubscriptionConfirmationMail;
use App\Models\Incident;
use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use App\Models\Package;
use App\Models\StatusPage;
use App\Models\StatusPageSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class StatusPageManagementTest extends TestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_status_page_form_renders_available_monitorings_and_default_components(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        Monitoring::factory()->for($user)->create(['name' => 'Primary API']);

        $testResponse = $this->actingAs($user)->get(route('status-pages.create'));

        $testResponse->assertOk();
        $testResponse->assertSeeText(__('status_page.form.components'));
        $testResponse->assertSeeHtml('API');
        $testResponse->assertSeeHtml('Web App');
        $testResponse->assertSeeHtml('Workers');
        $testResponse->assertSeeHtml('Database');
        $testResponse->assertSeeText('Primary API');
        $testResponse->assertDontSeeHtml('name="slug"');
    }

    public function test_public_status_page_urls_use_ulids_and_legacy_slugs_redirect_to_the_canonical_url(): void
    {
        $package = Package::factory()->create();
        $user = User::factory()->create(['package_id' => $package->id]);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'is_public' => true,
        ]);
        $sameNameStatusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => null,
            'is_public' => true,
        ]);

        $canonicalUrl = route('public-status-pages.show', $statusPage);
        $sameNameCanonicalUrl = route('public-status-pages.show', $sameNameStatusPage);

        $this->assertStringEndsWith('/status/' . $statusPage->id, $canonicalUrl);
        $this->assertStringNotContainsString('acme-status', $canonicalUrl);
        $this->assertNotSame($canonicalUrl, $sameNameCanonicalUrl);

        $this->get($canonicalUrl)->assertOk()->assertSeeHtml('<link rel="canonical" href="' . $canonicalUrl . '">');
        $this->get($sameNameCanonicalUrl)->assertOk();
        $this->get(route('legacy-public-status-pages.show', 'acme-status'))
            ->assertRedirect($canonicalUrl)
            ->assertStatus(301);

        $statusPage->update(['name' => 'Renamed Status']);

        $this->assertSame($canonicalUrl, route('public-status-pages.show', $statusPage->refresh()));
        $this->get($canonicalUrl)->assertOk()->assertSeeText('Renamed Status');
    }

    public function test_unknown_or_private_public_status_page_identifiers_return_not_found(): void
    {
        $package = Package::factory()->create();
        $privateStatusPage = StatusPage::query()->create([
            'user_id' => User::factory()->create(['package_id' => $package->id])->id,
            'name' => 'Private Status',
            'slug' => 'private-status',
            'is_public' => false,
        ]);

        $this->get(route('public-status-pages.show', Str::ulid()->toBase32()))->assertNotFound();
        $this->get(route('legacy-public-status-pages.show', 'unknown-status'))->assertNotFound();
        $this->get(route('legacy-public-status-pages.show', $privateStatusPage->slug))->assertNotFound();
    }

    public function test_legacy_subscription_urls_redirect_to_ulid_routes_without_changing_request_methods(): void
    {
        $package = Package::factory()->create();
        $statusPage = StatusPage::query()->create([
            'user_id' => User::factory()->create(['package_id' => $package->id])->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'is_public' => true,
        ]);
        StatusPageSubscription::query()->create([
            'status_page_id' => $statusPage->id,
            'email' => 'customer@example.com',
            'confirmation_token_hash' => StatusPageSubscription::hashToken('confirm-token'),
            'unsubscribe_token' => 'unsubscribe-token',
        ]);

        $this->post(route('legacy-public-status-pages.subscribers.store', $statusPage->slug))
            ->assertRedirect(route('public-status-pages.subscribers.store', $statusPage))
            ->assertStatus(307);
        $this->get(route('legacy-public-status-pages.subscribers.confirm', [
            'statusPageSlug' => $statusPage->slug,
            'token' => 'confirm-token',
        ]))->assertRedirect(route('public-status-pages.subscribers.confirm', [
            'statusPage' => $statusPage,
            'token' => 'confirm-token',
        ]));
        $this->get(route('legacy-public-status-pages.subscribers.unsubscribe', [
            'statusPageSlug' => $statusPage->slug,
            'token' => 'unsubscribe-token',
        ]))->assertRedirect(route('public-status-pages.subscribers.unsubscribe', [
            'statusPage' => $statusPage,
            'token' => 'unsubscribe-token',
        ]));
        $this->delete(route('legacy-public-status-pages.subscribers.destroy', [
            'statusPageSlug' => $statusPage->slug,
            'token' => 'unsubscribe-token',
        ]))->assertRedirect(route('public-status-pages.subscribers.destroy', [
            'statusPage' => $statusPage,
            'token' => 'unsubscribe-token',
        ]))->assertStatus(307);
    }

    public function test_user_can_create_component_based_status_page(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $apiMonitoring = Monitoring::factory()->for($user)->create(['name' => 'Primary API']);
        $workerMonitoring = Monitoring::factory()->for($user)->create(['name' => 'Queue Worker']);

        $testResponse = $this->actingAs($user)->post(route('status-pages.store'), [
            'name' => 'Acme Status',
            'description' => 'Customer-facing status page',
            'is_public' => '1',
            'components' => [
                [
                    'name' => 'API',
                    'monitoring_ids' => [$apiMonitoring->id],
                ],
                [
                    'name' => 'Workers',
                    'monitoring_ids' => [$workerMonitoring->id],
                ],
            ],
        ]);

        $statusPage = StatusPage::query()->firstOrFail();

        $testResponse->assertRedirect(route('status-pages.show', $statusPage));
        $this->assertDatabaseHas('status_pages', [
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => null,
            'is_public' => true,
        ]);
        $this->assertDatabaseHas('status_page_components', [
            'status_page_id' => $statusPage->id,
            'name' => 'API',
            'position' => 0,
            'source_type' => 'manual',
            'monitoring_group_id' => null,
        ]);
        $this->assertDatabaseHas('status_page_components', [
            'status_page_id' => $statusPage->id,
            'name' => 'Workers',
            'position' => 1,
            'source_type' => 'manual',
            'monitoring_group_id' => null,
        ]);
        $this->assertDatabaseHas('status_page_component_monitoring', [
            'monitoring_id' => $apiMonitoring->id,
            'position' => 0,
        ]);
    }

    public function test_user_can_list_show_edit_update_and_delete_status_page(): void
    {
        Date::setTestNow('2026-06-27 10:00:00');

        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Primary API']);
        $group = MonitoringGroup::factory()->for($user)->create(['name' => 'Core Group']);
        $group->monitorings()->attach($monitoring->id);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Old Status',
            'slug' => 'old-status',
            'description' => 'Old description',
            'is_public' => true,
        ]);
        $statusPageComponent = $statusPage->components()->create([
            'name' => 'Core',
            'position' => 0,
            'source_type' => StatusPageComponentSource::MONITORING_GROUP,
            'monitoring_group_id' => $group->id,
        ]);
        $statusPageComponent->monitorings()->attach($monitoring->id, ['position' => 0]);
        Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::now()->subDay(),
            'up_at' => Date::now()->subHours(23),
        ]);

        $this->actingAs($user)->get(route('status-pages.index'))
            ->assertOk()
            ->assertSeeText('Old Status');

        $this->actingAs($user)->get(route('status-pages.show', $statusPage))
            ->assertOk()
            ->assertSeeText('Old Status');

        $this->actingAs($user)->get(route('status-pages.edit', $statusPage))
            ->assertOk()
            ->assertSeeText('Old Status')
            ->assertSeeText('Primary API');

        $this->actingAs($user)->put(route('status-pages.update', $statusPage), [
            'name' => 'Updated Status',
            'description' => 'Updated description',
            'is_public' => '0',
            'components' => [
                [
                    'name' => 'Updated API',
                    'source_type' => StatusPageComponentSource::MANUAL->value,
                    'monitoring_ids' => [$monitoring->id],
                ],
                [
                    'name' => 'Updated Group',
                    'source_type' => StatusPageComponentSource::MONITORING_GROUP->value,
                    'monitoring_group_id' => $group->id,
                ],
            ],
        ])->assertRedirect(route('status-pages.show', $statusPage))
            ->assertSessionHas('success', __('status_page.messages.updated'));

        $this->assertDatabaseHas('status_pages', [
            'id' => $statusPage->id,
            'name' => 'Updated Status',
            'slug' => 'old-status',
            'is_public' => false,
        ]);
        $this->assertDatabaseHas('status_page_components', [
            'status_page_id' => $statusPage->id,
            'name' => 'Updated Group',
            'source_type' => StatusPageComponentSource::MONITORING_GROUP->value,
            'monitoring_group_id' => $group->id,
        ]);

        $this->actingAs($user)->delete(route('status-pages.destroy', $statusPage))
            ->assertRedirect(route('status-pages.index'))
            ->assertSessionHas('success', __('status_page.messages.deleted'));

        $this->assertDatabaseMissing('status_pages', ['id' => $statusPage->id]);
    }

    public function test_demo_user_cannot_open_mutating_status_page_screens(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $demoUser = User::factory()->create([
            'package_id' => $package->id,
            'role' => UserRole::DEMO,
        ]);
        $statusPage = StatusPage::query()->create([
            'user_id' => $demoUser->id,
            'name' => 'Demo Status',
            'slug' => 'demo-status',
            'is_public' => true,
        ]);

        $this->actingAs($demoUser)->get(route('status-pages.create'))->assertForbidden();
        $this->actingAs($demoUser)->get(route('status-pages.edit', $statusPage))->assertForbidden();
        $this->actingAs($demoUser)->delete(route('status-pages.destroy', $statusPage))->assertForbidden();
    }

    public function test_status_page_group_components_cannot_reference_another_users_groups(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $otherUser = User::factory()->create(['package_id' => $package->id]);
        $foreignGroup = MonitoringGroup::factory()->for($otherUser)->create();

        $testResponse = $this->from(route('status-pages.create'))
            ->actingAs($user)
            ->post(route('status-pages.store'), [
                'name' => 'Acme Status',
                'slug' => 'acme-status',
                'is_public' => '1',
                'components' => [
                    [
                        'name' => 'Foreign Group',
                        'source_type' => 'monitoring_group',
                        'monitoring_group_id' => $foreignGroup->id,
                    ],
                ],
            ]);

        $testResponse->assertRedirect(route('status-pages.create'));
        $testResponse->assertSessionHasErrors(['components.0.monitoring_group_id']);
        $this->assertDatabaseMissing('status_pages', [
            'user_id' => $user->id,
            'name' => 'Acme Status',
        ]);
    }

    public function test_status_page_components_cannot_reference_another_users_monitorings(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $otherUser = User::factory()->create(['package_id' => $package->id]);
        $otherMonitoring = Monitoring::factory()->for($otherUser)->create();

        $testResponse = $this->from(route('status-pages.create'))
            ->actingAs($user)
            ->post(route('status-pages.store'), [
                'name' => 'Acme Status',
                'slug' => 'acme-status',
                'is_public' => '1',
                'components' => [
                    [
                        'name' => 'API',
                        'monitoring_ids' => [$otherMonitoring->id],
                    ],
                ],
            ]);

        $testResponse->assertRedirect(route('status-pages.create'));
        $testResponse->assertSessionHasErrors(['components.0.monitoring_ids.0']);
        $this->assertDatabaseMissing('status_pages', [
            'user_id' => $user->id,
            'name' => 'Acme Status',
        ]);
    }

    public function test_user_cannot_view_another_users_status_page_management_screen(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $owner = User::factory()->create(['package_id' => $package->id]);
        $otherUser = User::factory()->create(['package_id' => $package->id]);
        $statusPage = StatusPage::query()->create([
            'user_id' => $owner->id,
            'name' => 'Owner Status',
            'slug' => 'owner-status',
            'is_public' => true,
        ]);

        $testResponse = $this->actingAs($otherUser)->get(route('status-pages.show', $statusPage));

        $testResponse->assertNotFound();
    }

    public function test_open_incident_context_expands_the_internal_response_workbench(): void
    {
        Date::setTestNow('2026-05-14 14:30:00');

        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Primary API']);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'is_public' => true,
        ]);
        $statusPageComponent = $statusPage->components()->create(['name' => 'API', 'position' => 0]);
        $statusPageComponent->monitorings()->attach($monitoring->id, ['position' => 0]);
        $incident = Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::now()->subMinutes(20),
        ]);

        $response = $this->actingAs($user)
            ->get(route('status-pages.show', [
                'statusPage' => $statusPage,
                'incident_id' => $incident->id,
            ]));

        $this->assertMatchesRegularExpression(
            '/<details id="incident-workbench-' . $incident->id . '"\s+open\s+class=/',
            $response->getContent()
        );
        $response->assertOk()->assertSeeText(__('status_page.incident_workbench.heading'));
    }

    public function test_user_can_add_manual_incident_update_to_status_page_incident(): void
    {
        Date::setTestNow('2026-05-14 14:30:00');

        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Primary API']);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'is_public' => true,
        ]);
        $statusPageComponent = $statusPage->components()->create(['name' => 'API', 'position' => 0]);
        $statusPageComponent->monitorings()->attach($monitoring->id, ['position' => 0]);
        $incident = Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::now()->subMinutes(20),
            'up_at' => null,
        ]);

        $testResponse = $this->actingAs($user)->post(
            route('status-pages.incident-updates.store', [$statusPage, $incident]),
            [
                'status' => IncidentUpdateStatus::IDENTIFIED->value,
                'message' => 'We found a saturated database connection pool and are applying a fix.',
            ]
        );

        $testResponse->assertRedirect(route('status-pages.show', $statusPage));
        $this->assertDatabaseHas('incident_updates', [
            'incident_id' => $incident->id,
            'status' => IncidentUpdateStatus::IDENTIFIED->value,
            'message' => 'We found a saturated database connection pool and are applying a fix.',
        ]);

        $publicResponse = $this->get(route('public-status-pages.show', $statusPage));

        $publicResponse->assertOk();
        $publicResponse->assertSeeText(__('status_page.incident_updates.statuses.identified'));
        $publicResponse->assertSeeText('We found a saturated database connection pool and are applying a fix.');
    }

    public function test_user_can_save_private_incident_review_notes_without_exposing_them_publicly(): void
    {
        Date::setTestNow('2026-05-14 14:30:00');

        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $monitoring = Monitoring::factory()->for($user)->create(['name' => 'Primary API']);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'is_public' => true,
        ]);
        $statusPageComponent = $statusPage->components()->create(['name' => 'API', 'position' => 0]);
        $statusPageComponent->monitorings()->attach($monitoring->id, ['position' => 0]);
        $incident = Incident::query()->create([
            'monitoring_id' => $monitoring->id,
            'down_at' => Date::now()->subMinutes(20),
            'up_at' => Date::now()->subMinutes(5),
        ]);

        $this->actingAs($user)->patch(
            route('status-pages.incident-review.update', [$statusPage, $incident]),
            [
                'problem_description' => '  A connection pool exhausted its available slots.  ',
                'resolution_description' => '  Increased the pool limit and restarted the affected worker.  ',
            ]
        )->assertRedirect(route('status-pages.show', $statusPage));

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'problem_description' => 'A connection pool exhausted its available slots.',
            'resolution_description' => 'Increased the pool limit and restarted the affected worker.',
        ]);

        $this->actingAs($user)->get(route('status-pages.show', $statusPage))
            ->assertSeeHtml('A connection pool exhausted its available slots.')
            ->assertSeeHtml('Increased the pool limit and restarted the affected worker.');

        $this->get(route('public-status-pages.show', $statusPage))
            ->assertOk()
            ->assertDontSeeText('A connection pool exhausted its available slots.')
            ->assertDontSeeText('Increased the pool limit and restarted the affected worker.');
    }

    public function test_user_cannot_update_incident_review_notes_for_an_incident_outside_status_page(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $includedMonitoring = Monitoring::factory()->for($user)->create();
        $outsideMonitoring = Monitoring::factory()->for($user)->create();
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'is_public' => true,
        ]);
        $statusPageComponent = $statusPage->components()->create(['name' => 'API', 'position' => 0]);
        $statusPageComponent->monitorings()->attach($includedMonitoring->id, ['position' => 0]);
        $incident = Incident::query()->create([
            'monitoring_id' => $outsideMonitoring->id,
            'down_at' => now()->subMinutes(20),
            'up_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($user)->patch(
            route('status-pages.incident-review.update', [$statusPage, $incident]),
            [
                'problem_description' => 'Should not be saved.',
                'resolution_description' => 'Should not be saved either.',
            ]
        )->assertNotFound();

        $this->assertDatabaseMissing('incidents', [
            'id' => $incident->id,
            'problem_description' => 'Should not be saved.',
        ]);
    }

    public function test_public_status_page_accepts_confirms_and_removes_email_subscriptions(): void
    {
        Mail::fake();

        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'is_public' => true,
        ]);

        $testResponse = $this->post(route('public-status-pages.subscribers.store', $statusPage), [
            'email' => 'Customer@Example.com',
        ]);

        $testResponse->assertRedirect(route('public-status-pages.show', $statusPage));
        $testResponse->assertSessionHas('status_page_subscription_success');

        $statusPageSubscription = StatusPageSubscription::query()->firstOrFail();
        $this->assertSame($statusPage->id, $statusPageSubscription->status_page_id);
        $this->assertSame('customer@example.com', $statusPageSubscription->email);
        $this->assertNull($statusPageSubscription->verified_at);
        $this->assertNotNull($statusPageSubscription->confirmation_token_hash);

        $confirmationToken = null;
        Mail::assertSent(PublicStatusPageSubscriptionConfirmationMail::class, function (PublicStatusPageSubscriptionConfirmationMail $publicStatusPageSubscriptionConfirmationMail) use (&$confirmationToken, $statusPageSubscription): bool {
            $confirmationToken = $publicStatusPageSubscriptionConfirmationMail->token;

            return $publicStatusPageSubscriptionConfirmationMail->hasTo('customer@example.com') && $publicStatusPageSubscriptionConfirmationMail->subscription->is($statusPageSubscription);
        });

        $this->get(route('public-status-pages.subscribers.confirm', [
            'statusPage' => $statusPage,
            'token' => $confirmationToken,
        ]))->assertRedirect(route('public-status-pages.show', $statusPage));

        $this->assertTrue($statusPageSubscription->refresh()->isVerified());
        $this->assertNull($statusPageSubscription->confirmation_token_hash);

        $unsubscribeResponse = $this->get(route('public-status-pages.subscribers.unsubscribe', [
            'statusPage' => $statusPage,
            'token' => $statusPageSubscription->unsubscribe_token,
        ]));

        $unsubscribeResponse->assertOk();
        $unsubscribeResponse->assertSeeHtml('data-confirm-message="' . __('status_page.public.subscribe.unsubscribe_confirmation') . '"');

        $this->delete(route('public-status-pages.subscribers.destroy', [
            'statusPage' => $statusPage,
            'token' => $statusPageSubscription->unsubscribe_token,
        ]), [
            'email' => 'CUSTOMER@example.com',
        ])->assertRedirect(route('public-status-pages.show', $statusPage));

        $this->assertDatabaseMissing('status_page_subscriptions', [
            'id' => $statusPageSubscription->id,
        ]);
    }

    public function test_user_cannot_add_incident_update_for_incident_outside_status_page(): void
    {
        $package = Package::factory()->create(['monitoring_limit' => 10]);
        $user = User::factory()->create(['package_id' => $package->id]);
        $includedMonitoring = Monitoring::factory()->for($user)->create();
        $outsideMonitoring = Monitoring::factory()->for($user)->create();
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'is_public' => true,
        ]);
        $statusPageComponent = $statusPage->components()->create(['name' => 'API', 'position' => 0]);
        $statusPageComponent->monitorings()->attach($includedMonitoring->id, ['position' => 0]);
        $incident = Incident::query()->create([
            'monitoring_id' => $outsideMonitoring->id,
            'down_at' => now()->subMinutes(20),
            'up_at' => null,
        ]);

        $testResponse = $this->actingAs($user)->post(
            route('status-pages.incident-updates.store', [$statusPage, $incident]),
            [
                'status' => IncidentUpdateStatus::INVESTIGATING->value,
                'message' => 'This should not be published here.',
            ]
        );

        $testResponse->assertNotFound();
        $this->assertDatabaseMissing('incident_updates', [
            'incident_id' => $incident->id,
            'message' => 'This should not be published here.',
        ]);
    }
}
