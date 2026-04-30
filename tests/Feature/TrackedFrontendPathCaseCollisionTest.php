<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class TrackedFrontendPathCaseCollisionTest extends TestCase
{
    public function test_tracked_frontend_paths_do_not_collide_on_case_insensitive_filesystems(): void
    {
        $trackedPaths = $this->trackedPaths('resources/js');

        $duplicates = collect($trackedPaths)
            ->groupBy(static fn (string $path): string => mb_strtolower($path))
            ->filter(static fn ($paths): bool => $paths->count() > 1)
            ->map(static fn ($paths): array => $paths->values()->all())
            ->values()
            ->all();

        $this->assertSame([], $duplicates, 'Found case-colliding tracked frontend paths: ' . json_encode($duplicates, JSON_THROW_ON_ERROR));
    }

    /**
     * @return list<string>
     */
    private function trackedPaths(string $path): array
    {
        $output = [];
        $exitCode = 0;

        exec(sprintf('git ls-files -- %s', escapeshellarg($path)), $output, $exitCode);

        $this->assertSame(0, $exitCode, 'Failed to read tracked paths from git.');

        return array_values(array_filter($output, static fn (string $line): bool => $line !== ''));
    }
}
