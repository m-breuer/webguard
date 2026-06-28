<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Api\MonitoringApiRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringApiRequestAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorization_allows_routes_without_monitoring_model(): void
    {
        $request = new class extends MonitoringApiRequest
        {
            public function rules(): array
            {
                return [];
            }
        };
        $request->setRouteResolver(fn (): object => new class
        {
            public function parameter(string $name, mixed $default = null): mixed
            {
                return $default;
            }
        });

        $this->assertTrue($request->authorize());
    }
}
