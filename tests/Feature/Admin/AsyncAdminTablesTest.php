<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\ApiLog;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class AsyncAdminTablesTest extends TestCase
{
    public function test_admin_async_table_views_render_the_shared_component(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $routes = [
            route('admin.users.index'),
            route('admin.packages.index'),
            route('admin.server-instances.index'),
            route('admin.apis.index'),
            route('admin.activity-logs.index'),
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin)
                ->get($route)->assertOk()->assertSeeHtml('asyncTable')
                ->assertSee(__('search.filter.heading'))
                ->assertSeeHtml('type="search"')
                ->assertDontSeeHtml('&#128269;')
                ->assertDontSeeHtml('pl-9');
        }
    }

    public function test_admin_delete_forms_use_app_confirm_dialog_instead_of_native_browser_confirm(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create();
        $package = Package::factory()->create();
        $serverInstance = ServerInstance::query()->create([
            'code' => 'confirm-dialog-instance',
            'ip_address' => '10.0.0.99',
            'api_key_hash' => 'secret',
            'is_active' => true,
        ]);

        $routes = [
            route('admin.users.index') => route('admin.users.destroy', $user),
            route('admin.packages.index') => route('admin.packages.destroy', $package),
            route('admin.server-instances.index') => route('admin.server-instances.destroy', $serverInstance),
        ];

        foreach ($routes as $route => $destroyRoute) {
            $this->actingAs($admin)
                ->get($route)
                ->assertOk()
                ->assertSeeHtml('x-data="confirmDialog()"')
                ->assertSeeHtml('data-confirm-message')
                ->assertSeeHtml('action="' . $destroyRoute . '"')
                ->assertDontSeeHtml('return confirm(');
        }
    }

    public function test_admin_user_table_supports_backend_search_filter_sorting_and_pagination(): void
    {
        Package::factory()->create(['monitoring_limit' => 5]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        User::factory()->create([
            'name' => 'Sortable Alpha',
            'email' => 'alpha@example.test',
            'role' => UserRole::REGULAR,
        ]);
        User::factory()->create([
            'name' => 'Sortable Zulu',
            'email' => 'zulu@example.test',
            'role' => UserRole::GUEST,
        ]);

        foreach (range(1, 6) as $index) {
            User::factory()->create(['name' => 'Paged User ' . $index]);
        }

        $testResponse = $this->actingAs($admin)->getJson(route('admin.users.index', [
            'search' => 'Sortable',
            'sort' => 'name',
            'direction' => 'desc',
            'per_page' => 5,
        ]));

        $testResponse->assertOk()->assertJsonPath('pagination.total', 2);
        $sortedHtml = $testResponse->json('html');
        $this->assertStringContainsString('Sortable Zulu', $sortedHtml);
        $this->assertLessThan(mb_strpos($sortedHtml, 'Sortable Alpha'), mb_strpos($sortedHtml, 'Sortable Zulu'));

        $filteredResponse = $this->actingAs($admin)->getJson(route('admin.users.index', [
            'role' => UserRole::GUEST->value,
            'per_page' => 5,
        ]));

        $filteredResponse->assertOk();
        $this->assertStringContainsString('zulu@example.test', $filteredResponse->json('html'));
        $this->assertStringNotContainsString('alpha@example.test', $filteredResponse->json('html'));

        $pagedResponse = $this->actingAs($admin)->getJson(route('admin.users.index', [
            'search' => 'Paged User',
            'per_page' => 5,
            'page' => 2,
        ]));

        $pagedResponse->assertOk()
            ->assertJsonPath('pagination.total', 6)
            ->assertJsonPath('pagination.current_page', 2);
    }

    public function test_admin_package_table_supports_backend_filtering_and_sorting(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        Package::factory()->create(['monitoring_limit' => 10, 'price' => 29.99, 'is_selectable' => true]);
        Package::factory()->create(['monitoring_limit' => 99, 'price' => 9.99, 'is_selectable' => false]);

        $testResponse = $this->actingAs($admin)->getJson(route('admin.packages.index', [
            'is_selectable' => '0',
            'sort' => 'price',
            'direction' => 'asc',
            'per_page' => 5,
        ]));

        $testResponse->assertOk()->assertJsonPath('pagination.total', 1);
        $this->assertStringContainsString('99', $testResponse->json('html'));
        $this->assertStringNotContainsString('29.99', $testResponse->json('html'));
    }

    public function test_admin_server_instance_table_supports_backend_search_health_filter_and_sorting(): void
    {
        Date::setTestNow('2026-05-04 12:00:00');
        $this->beforeApplicationDestroyed(fn (): mixed => Date::setTestNow());

        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        ServerInstance::query()->create([
            'code' => 'async-healthy',
            'ip_address' => '10.0.0.10',
            'api_key_hash' => 'secret',
            'is_active' => true,
            'last_seen_at' => Date::now(),
        ]);
        ServerInstance::query()->create([
            'code' => 'async-stale',
            'ip_address' => '10.0.0.20',
            'api_key_hash' => 'secret',
            'is_active' => true,
            'last_seen_at' => Date::now()->subHour(),
        ]);

        $testResponse = $this->actingAs($admin)->getJson(route('admin.server-instances.index', [
            'search' => 'async',
            'health' => 'stale',
            'sort' => 'code',
            'direction' => 'asc',
            'per_page' => 5,
        ]));

        $testResponse->assertOk()->assertJsonPath('pagination.total', 1);
        $this->assertStringContainsString('async-stale', $testResponse->json('html'));
        $this->assertStringNotContainsString('async-healthy', $testResponse->json('html'));
    }

    public function test_admin_api_log_table_supports_backend_user_filter_search_and_email_sorting(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $alpha = User::factory()->create(['email' => 'alpha-api@example.test']);
        $zulu = User::factory()->create(['email' => 'zulu-api@example.test']);

        ApiLog::query()->create(['user_id' => $zulu->id, 'route' => '/api/monitorings']);
        ApiLog::query()->create(['user_id' => $alpha->id, 'route' => '/api/status']);

        $testResponse = $this->actingAs($admin)->getJson(route('admin.apis.index', [
            'user_id' => $zulu->id,
            'search' => 'monitorings',
        ]));

        $testResponse->assertOk()->assertJsonPath('pagination.total', 1);
        $this->assertStringContainsString('zulu-api@example.test', $testResponse->json('html'));
        $this->assertStringNotContainsString('alpha-api@example.test', $testResponse->json('html'));

        $sortedResponse = $this->actingAs($admin)->getJson(route('admin.apis.index', [
            'sort' => 'email',
            'direction' => 'asc',
        ]));

        $sortedHtml = $sortedResponse->json('html');
        $this->assertLessThan(mb_strpos($sortedHtml, 'zulu-api@example.test'), mb_strpos($sortedHtml, 'alpha-api@example.test'));
    }

    public function test_admin_activity_log_table_supports_backend_filters_search_and_sorting(): void
    {
        Package::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        activity('user')
            ->causedBy($admin)
            ->event('updated')
            ->log('async_profile_changed');

        activity('monitoring')
            ->event('created')
            ->log('monitoring_created');

        $testResponse = $this->actingAs($admin)->getJson(route('admin.activity-logs.index', [
            'search' => 'async_profile',
            'log_name' => 'user',
            'event' => 'updated',
            'sort' => 'description',
            'direction' => 'asc',
            'per_page' => 5,
        ]));

        $testResponse->assertOk()->assertJsonPath('pagination.total', 1);
        $this->assertStringContainsString('async_profile_changed', $testResponse->json('html'));
        $this->assertStringNotContainsString('monitoring_created', $testResponse->json('html'));
    }
}
