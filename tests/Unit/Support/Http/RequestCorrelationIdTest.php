<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Http;

use App\Support\Http\RequestCorrelationId;
use Illuminate\Http\Request;
use Tests\TestCase;

class RequestCorrelationIdTest extends TestCase
{
    public function test_it_preserves_gateway_and_uuid_request_identifiers(): void
    {
        $gatewayRequestId = str_repeat('a', 32);
        $uuidRequestId = '863928e0-9ff8-4e53-b7f7-9dddb101651c';

        $this->assertSame(
            $gatewayRequestId,
            RequestCorrelationId::for(Request::create('/', 'GET', server: ['HTTP_X_REQUEST_ID' => $gatewayRequestId]))
        );
        $this->assertSame(
            $uuidRequestId,
            RequestCorrelationId::for(Request::create('/', 'GET', server: ['HTTP_X_REQUEST_ID' => $uuidRequestId]))
        );
    }

    public function test_it_replaces_untrusted_request_identifiers(): void
    {
        $requestId = RequestCorrelationId::for(Request::create('/', 'GET', server: [
            'HTTP_X_REQUEST_ID' => 'monitoring-target=https://private.example.test',
        ]));

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $requestId
        );
    }
}
