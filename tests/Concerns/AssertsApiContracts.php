<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\ServerInstance;
use Illuminate\Testing\TestResponse;

trait AssertsApiContracts
{
    /**
     * @param  list<string>  $paths
     */
    protected function assertDataEnvelope(TestResponse $testResponse, array $paths = []): TestResponse
    {
        $testResponse->assertOk()->assertJsonStructure(['data']);

        foreach ($paths as $path) {
            $this->assertNotNull($testResponse->json($path), sprintf('Expected the response to include [%s].', $path));
        }

        return $testResponse;
    }

    protected function assertInternalUiTelemetry(TestResponse $testResponse, int $maximumQueries, int $maximumBytes): void
    {
        $testResponse
            ->assertHeader('X-Request-Id')
            ->assertHeader('X-Query-Count')
            ->assertHeader('X-Response-Bytes')
            ->assertHeader('Server-Timing');

        $this->assertLessThanOrEqual($maximumQueries, (int) $testResponse->headers->get('X-Query-Count'));
        $this->assertLessThanOrEqual($maximumBytes, (int) $testResponse->headers->get('X-Response-Bytes'));
    }

    /**
     * @return array<string, string>
     */
    protected function instanceHeaders(ServerInstance $serverInstance, string $apiKey = 'test-token-1234567890'): array
    {
        return [
            'X-INSTANCE-CODE' => $serverInstance->code,
            'X-API-KEY' => $apiKey,
        ];
    }
}
