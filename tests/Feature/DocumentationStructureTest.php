<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class DocumentationStructureTest extends TestCase
{
    public function test_readme_is_a_concise_entrypoint_to_expanded_docs(): void
    {
        $readme = file_get_contents(base_path('README.md'));

        $this->assertIsString($readme);
        $this->assertLessThanOrEqual(50, mb_substr_count($readme, PHP_EOL) + 1);

        foreach ($this->expectedDocumentationLinks() as $link) {
            $this->assertStringContainsString("]({$link})", $readme);
            $this->assertFileExists(base_path($link));
        }
    }

    public function test_docs_preserve_detailed_readme_topics(): void
    {
        $topicsByDocument = [
            'docs/features.md' => [
                'Heartbeat and cron monitoring',
                'Expected HTTP status ranges',
                'Weekly monitoring digest',
                'Public status pages',
            ],
            'docs/architecture.md' => [
                'Laravel 13',
                'Laravel Sanctum',
                'Redis',
                'Pest',
                'Chart.js',
            ],
            'docs/installation.md' => [
                'Docker Deployment',
                'Docker Local Development',
                'Native Setup Without Docker',
                'composer install',
                'bun install',
                'php artisan migrate',
                'queue:work redis',
                'webguard-instance Integration With Local Docker',
            ],
            'docs/contributing.md' => [
                'Conventional Commits',
                'Write tests',
                'pull request',
            ],
        ];

        foreach ($topicsByDocument as $document => $topics) {
            $contents = file_get_contents(base_path($document));

            $this->assertIsString($contents);

            foreach ($topics as $topic) {
                $this->assertStringContainsString($topic, $contents);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function expectedDocumentationLinks(): array
    {
        return [
            'docs/features.md',
            'docs/architecture.md',
            'docs/installation.md',
            'docs/contributing.md',
        ];
    }
}
