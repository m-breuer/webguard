<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Package;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

class AuthMailDesignTest extends TestCase
{
    public function test_framework_notification_mails_use_corporate_mail_design(): void
    {
        Package::factory()->create();

        $user = User::factory()->create();

        $html = (string) (new ResetPassword('reset-token'))->toMail($user)->render();

        $this->assertStringContainsString('font-family: Sen', $html);
        $this->assertStringContainsString('background-color: #f1f5f9', $html);
        $this->assertStringContainsString('class="mail-logo-mark"', $html);
        $this->assertStringContainsString(e(__('mail.general.brand_subtitle')), $html);
        $this->assertStringContainsString('border-radius: 16px', $html);
        $this->assertStringContainsString('background-color: #10b981', $html);
        $this->assertStringContainsString(route('monitoring-locations'), $html);
        $this->assertStringContainsString(route('terms-of-use'), $html);
    }
}
