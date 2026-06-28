<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\DnsRecordExpectation;
use InvalidArgumentException;
use Tests\TestCase;

class DnsRecordExpectationTest extends TestCase
{
    public function test_record_types_and_record_type_normalization(): void
    {
        $this->assertSame(['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SOA', 'CAA'], DnsRecordExpectation::recordTypes());
        $this->assertSame('AAAA', DnsRecordExpectation::normalizeRecordType(' aaaa '));
        $this->assertNull(DnsRecordExpectation::normalizeRecordType('   '));
        $this->assertNull(DnsRecordExpectation::normalizeRecordType(['A']));
    }

    public function test_expected_values_are_parsed_normalized_deduplicated_and_sorted(): void
    {
        $this->assertSame([], DnsRecordExpectation::normalizeValues(null, 'TXT'));
        $this->assertSame([], DnsRecordExpectation::normalizeValues(" \n ", 'TXT'));
        $this->assertSame(['192.0.2.1', '192.0.2.2'], DnsRecordExpectation::normalizeValues("192.0.2.2\n192.0.2.1\n192.0.2.1", 'A'));
        $this->assertSame(['2001:db8::1'], DnsRecordExpectation::normalizeValues(['2001:DB8::1'], 'AAAA'));
        $this->assertSame(['mail.example.com'], DnsRecordExpectation::normalizeValues('MAIL.Example.COM.', 'CNAME'));
        $this->assertSame(['10 mail.example.com'], DnsRecordExpectation::normalizeValues('010 MAIL.Example.COM.', 'MX'));
        $this->assertSame(['plain text'], DnsRecordExpectation::normalizeValues('  plain   text  ', 'TXT'));
        $this->assertSame(['ns.example.com'], DnsRecordExpectation::normalizeValues('["NS.Example.COM."]', 'NS'));
    }

    public function test_expected_values_reject_invalid_shapes_and_addresses(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DnsRecordExpectation::normalizeValues(['192.0.2.1', ['nested']], 'A');
    }

    public function test_expected_values_reject_invalid_json(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DnsRecordExpectation::normalizeValues('[not-json', 'TXT');
    }

    public function test_expected_values_reject_invalid_ip_records(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DnsRecordExpectation::normalizeValues('example.com', 'A');
    }
}
