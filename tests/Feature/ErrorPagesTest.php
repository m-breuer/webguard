<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: int, 2: string}>
     */
    public static function brandedErrorViewProvider(): array
    {
        return [
            'forbidden' => ['403', 403, '403'],
            'session expired' => ['419', 419, '419'],
            'too many requests' => ['429', 429, '429'],
            'server error' => ['500', 500, '500'],
            'unavailable' => ['503', 503, '503'],
            'unknown client error fallback' => ['4xx', 405, '400'],
            'unknown server error fallback' => ['5xx', 502, '500'],
        ];
    }

    public function test_not_found_page_uses_the_webguard_error_design(): void
    {
        $testResponse = $this->get('/webguard-error-page-that-does-not-exist');

        $testResponse->assertNotFound();
        $testResponse->assertSeeText('404');
        $testResponse->assertSeeText(__('errors.status.404.title'));
        $testResponse->assertSeeText(__('errors.eyebrow'));
        $testResponse->assertSee(__('app.logo_alt'));
    }

    public function test_error_page_uses_the_active_application_locale(): void
    {
        app()->setLocale('de');

        $html = view('errors.404')->render();

        $this->assertStringContainsString('Dieses Signal wurde nicht gefunden.', $html);
    }

    #[DataProvider('brandedErrorViewProvider')]
    public function test_common_error_views_render_with_their_status_content(string $view, int $status, string $contentStatus): void
    {
        $html = view('errors.' . $view, [
            'exception' => new HttpException($status),
        ])->render();

        $this->assertStringContainsString((string) $status, $html);
        $this->assertStringContainsString((string) __('errors.status.' . $contentStatus . '.title'), $html);
        $this->assertStringContainsString((string) __('errors.eyebrow'), $html);
    }
}
