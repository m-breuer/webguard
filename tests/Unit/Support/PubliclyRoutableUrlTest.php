<?php

declare(strict_types=1);

namespace App\Support {
    function dns_get_record(string $hostname, int $type): array|false
    {
        $records = $GLOBALS['publicly_routable_url_dns_records'][$hostname] ?? null;

        if ($records !== null) {
            return $records;
        }

        return \dns_get_record($hostname, $type);
    }
}

namespace Tests\Unit\Support {
    use App\Support\PubliclyRoutableUrl;
    use Tests\TestCase;

    class PubliclyRoutableUrlTest extends TestCase
    {
        protected function tearDown(): void
        {
            unset($GLOBALS['publicly_routable_url_dns_records']);

            parent::tearDown();
        }

        public function test_it_allows_public_http_urls_without_dns_resolution(): void
        {
            $this->assertTrue(PubliclyRoutableUrl::allows('https://example.com/webhooks/monitoring'));
            $this->assertTrue(PubliclyRoutableUrl::allows('http://example.org/notify'));
        }

        public function test_it_rejects_non_public_url_shapes(): void
        {
            foreach ([
                'not-a-url',
                'ftp://example.com/webhook',
                'https://localhost/webhook',
                'https://service.local/webhook',
                'https://internal/webhook',
                'http://127.0.0.1/webhook',
                'http://[::1]/webhook',
            ] as $url) {
                $this->assertFalse(PubliclyRoutableUrl::allows($url), $url);
            }
        }

        public function test_it_rejects_hosts_that_resolve_to_private_addresses(): void
        {
            $GLOBALS['publicly_routable_url_dns_records'] = [
                'private.example.com' => [
                    ['ip' => '10.10.1.5'],
                ],
            ];

            $this->assertTrue(PubliclyRoutableUrl::allows('https://private.example.com/webhook'));
            $this->assertFalse(PubliclyRoutableUrl::allows('https://private.example.com/webhook', resolveDns: true));
        }
    }
}
