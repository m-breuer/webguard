<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\MonitoringGroup;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\StatusPage;
use App\Models\User;
use Tests\TestCase;

class FormModalCrudFlowTest extends TestCase
{
    public function test_monitoring_create_and_edit_endpoints_return_modal_fragments_and_index_exposes_triggers(): void
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

        $this->actingAs($user)->get(route('monitorings.create', ['modal' => 1]))
            ->assertOk()
            ->assertSeeHtml('name="modal_form"')
            ->assertSeeHtml('action="' . route('monitorings.store') . '"')
            ->assertSeeHtml('data-monitoring-type-control')
            ->assertSeeHtml('data-monitoring-type-fields="port"')
            ->assertDontSeeHtml('x-model="type"')
            ->assertDontSeeHtml('autofocus');
        $this->actingAs($user)->get(route('monitorings.create'))
            ->assertOk()
            ->assertSeeHtml('autofocus');
        $this->actingAs($user)->get(route('monitorings.edit', [$monitoring, 'modal' => 1]))
            ->assertOk()
            ->assertSeeHtml('name="modal_form"')
            ->assertSeeHtml('action="' . route('monitorings.update', $monitoring) . '"');
        $this->actingAs($user)->get(route('monitorings.index'))
            ->assertOk()
            ->assertSeeHtml('data-form-modal-name="monitoring-form-modal"');
    }

    public function test_monitoring_group_create_and_edit_endpoints_return_modal_fragments(): void
    {
        $user = User::factory()->create(['package_id' => Package::factory()->create()->id]);
        $monitoringGroup = MonitoringGroup::factory()->for($user)->create();

        $this->actingAs($user)->get(route('monitoring-groups.create', ['modal' => 1]))
            ->assertOk()
            ->assertSeeHtml('name="modal_form"')
            ->assertSeeHtml('action="' . route('monitoring-groups.store') . '"');
        $this->actingAs($user)->get(route('monitoring-groups.edit', [$monitoringGroup, 'modal' => 1]))
            ->assertOk()
            ->assertSeeHtml('name="modal_form"')
            ->assertSeeHtml('action="' . route('monitoring-groups.update', $monitoringGroup) . '"');
        $this->actingAs($user)->get(route('monitoring-groups.index'))
            ->assertOk()
            ->assertSeeHtml('data-form-modal-name="monitoring-group-form-modal"');
    }

    public function test_status_page_create_and_edit_endpoints_return_modal_fragments(): void
    {
        $user = User::factory()->create(['package_id' => Package::factory()->create()->id]);
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'is_public' => true,
        ]);

        $this->actingAs($user)->get(route('status-pages.create', ['modal' => 1]))
            ->assertOk()
            ->assertSeeHtml('name="modal_form"')
            ->assertSeeHtml('action="' . route('status-pages.store') . '"');
        $this->actingAs($user)->get(route('status-pages.edit', [$statusPage, 'modal' => 1]))
            ->assertOk()
            ->assertSeeHtml('name="modal_form"')
            ->assertSeeHtml('action="' . route('status-pages.update', $statusPage) . '"')
            ->assertSeeHtml('data-status-page-modal-form')
            ->assertDontSeeHtml('shadow-md rounded-lg');
        $this->actingAs($user)->get(route('status-pages.edit', $statusPage))
            ->assertOk()
            ->assertSeeHtml('shadow-md rounded-lg');
        $this->actingAs($user)->get(route('status-pages.index'))
            ->assertOk()
            ->assertSeeHtml('data-form-modal-name="status-page-form-modal"');
    }

    public function test_validation_redirects_reopen_the_matching_modal_context(): void
    {
        $user = User::factory()->create(['package_id' => Package::factory()->create()->id]);
        $monitoringGroup = MonitoringGroup::factory()->for($user)->create();
        $statusPage = StatusPage::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Status',
            'is_public' => true,
        ]);

        $testResponse = $this->actingAs($user)
            ->from(route('monitorings.index'))
            ->post(route('monitorings.store'), ['modal_form' => 'monitoring-create']);
        $testResponse->assertRedirect(route('monitorings.index', ['modal' => 'monitoring-create']));
        $this->get($testResponse->headers->get('Location'))
            ->assertOk()
            ->assertSeeText(__('monitoring.form.sections.basic'));

        $groupResponse = $this->actingAs($user)
            ->from(route('monitoring-groups.index'))
            ->post(route('monitoring-groups.update', $monitoringGroup), [
                '_method' => 'PATCH',
                'modal_form' => 'monitoring-group-edit',
            ]);
        $groupResponse->assertRedirect(route('monitoring-groups.index', [
            'modal' => 'monitoring-group-edit',
            'monitoring_group' => $monitoringGroup->getRouteKey(),
        ]));

        $statusResponse = $this->actingAs($user)
            ->from(route('status-pages.index'))
            ->patch(route('status-pages.update', $statusPage), [
                'modal_form' => 'status-page-edit',
            ]);
        $statusResponse->assertRedirect(route('status-pages.index', [
            'modal' => 'status-page-edit',
            'status_page' => $statusPage->getRouteKey(),
        ]));
    }
}
