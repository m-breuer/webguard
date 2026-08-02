<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\NotificationEventType;
use App\Enums\StatusPageComponentSource;
use App\Enums\SupportedLanguage;
use Tests\TestCase;

class EnumHelperTest extends TestCase
{
    public function test_notification_event_type_values_return_all_wire_values(): void
    {
        $this->assertSame([
            'incident',
            'recovery',
            'performance_degraded',
            'performance_recovered',
            'ssl_expiring',
            'ssl_expired',
            'domain_expiring',
            'domain_expired',
        ], NotificationEventType::values());
    }

    public function test_status_page_component_source_values_return_all_wire_values(): void
    {
        $this->assertSame([
            'manual',
            'monitoring_group',
        ], StatusPageComponentSource::values());
    }

    public function test_supported_language_helpers_expose_labels_and_cookie_contract(): void
    {
        $this->assertSame(['en', 'de'], SupportedLanguage::values());
        $this->assertSame(SupportedLanguage::EN, SupportedLanguage::default());
        $this->assertSame([
            'en' => 'English',
            'de' => 'Deutsch',
        ], SupportedLanguage::toArray());
        $this->assertTrue(SupportedLanguage::isSupported('en'));
        $this->assertFalse(SupportedLanguage::isSupported(null));
        $this->assertFalse(SupportedLanguage::isSupported('fr'));
        $this->assertSame('webguard_locale', SupportedLanguage::cookieName());
        $this->assertSame(525600, SupportedLanguage::cookieDurationMinutes());
    }
}
