<?php

declare(strict_types=1);

use App\Enums\SupportedLanguage;

dataset('welcome-monitoring-interval-copy', [
    'english plural' => ['en', 5, '5 minutes'],
    'english singular' => ['en', 1, '1 minute'],
    'german plural' => ['de', 5, '5 Minuten'],
    'german singular' => ['de', 1, '1 Minute'],
]);

it('renders the configured monitoring interval copy on the welcome page', function (string $locale, int $interval, string $expectedCopy) {
    config(['monitoring.interval' => $interval]);

    $testResponse = $this->withCookie(SupportedLanguage::cookieName(), $locale)->get('/');

    $testResponse->assertOk();
    $testResponse->assertSeeText($expectedCopy);
})->with('welcome-monitoring-interval-copy');
