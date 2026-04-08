<?php

declare(strict_types=1);

use App\Enums\SupportedLanguage;

dataset('welcome-monitoring-interval-copy', [
    'english plural' => ['en', 5, '5 minutes'],
    'english singular' => ['en', 1, '1 minute'],
    'german plural' => ['de', 5, '5 Minuten'],
    'german singular' => ['de', 1, '1 Minute'],
]);

dataset('welcome-monitoring-interval-copy-per-request', [
    'english request update' => ['en', '5 minutes', '1 minute'],
    'german request update' => ['de', '5 Minuten', '1 Minute'],
]);

it('renders the configured monitoring interval copy on the welcome page', function (string $locale, int $interval, string $expectedCopy) {
    config(['monitoring.interval' => $interval]);

    $testResponse = $this->withCookie(SupportedLanguage::cookieName(), $locale)->get('/');

    $testResponse->assertOk();
    $testResponse->assertSeeText($expectedCopy);
})->with('welcome-monitoring-interval-copy');

it('updates the monitoring interval copy for each request', function (string $locale, string $firstExpectedCopy, string $secondExpectedCopy) {
    config(['monitoring.interval' => 5]);

    $firstResponse = $this->withCookie(SupportedLanguage::cookieName(), $locale)->get('/');

    $firstResponse->assertOk();
    $firstResponse->assertSeeText($firstExpectedCopy);

    config(['monitoring.interval' => 1]);

    $secondResponse = $this->withCookie(SupportedLanguage::cookieName(), $locale)->get('/');

    $secondResponse->assertOk();
    $secondResponse->assertSeeText($secondExpectedCopy);
})->with('welcome-monitoring-interval-copy-per-request');
