<?php

declare(strict_types=1);

/**
 * Generate release notes for a tag from git history.
 *
 * The output is intentionally based on local git data only so it can be used
 * from tag workflows and from manual backfills for existing tags.
 */
$options = getopt('', [
    'tag:',
    'repository::',
    'output::',
]);

$tag = isset($options['tag']) ? clean((string) $options['tag']) : '';
$repository = isset($options['repository']) ? clean((string) $options['repository']) : '';
$output = isset($options['output']) ? clean((string) $options['output']) : '';

if ($tag === '') {
    $tag = clean(run(['git', 'describe', '--tags', '--abbrev=0']));
}

assertTagExists($tag);

$previousTag = previousTag($tag);
$date = clean(run(['git', 'log', '-1', '--format=%cs', $tag]));
$commits = commitsForTag($tag, $previousTag);
$sections = groupConventionalCommits($commits);
$mergedBranches = mergedBranches($commits, $repository);

$markdown = renderChangelog($tag, $date, $previousTag, $sections, $mergedBranches);

if ($output !== '') {
    file_put_contents($output, $markdown);

    exit(0);
}

echo $markdown;

/**
 * @param  list<string>  $command
 */
function run(array $command): string
{
    $process = proc_open(
        $command,
        [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes
    );

    if (! is_resource($process)) {
        fwrite(STDERR, 'Unable to start process: ' . implode(' ', $command) . PHP_EOL);

        exit(1);
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        fwrite(STDERR, clean((string) $stderr) . PHP_EOL);

        exit($exitCode);
    }

    return (string) $stdout;
}

function assertTagExists(string $tag): void
{
    run(['git', 'rev-parse', '--verify', '--quiet', $tag]);
}

function clean(string $value): string
{
    return preg_replace('/^\s+|\s+$/u', '', $value) ?? '';
}

function previousTag(string $tag): ?string
{
    $tags = array_values(array_filter(explode(PHP_EOL, clean(run(['git', 'tag', '--sort=version:refname'])))));
    $index = array_search($tag, $tags, true);

    if ($index === false || $index === 0) {
        return null;
    }

    return $tags[$index - 1];
}

/**
 * @return list<array{hash: string, subject: string, body: string}>
 */
function commitsForTag(string $tag, ?string $previousTag): array
{
    $range = $previousTag ? "{$previousTag}..{$tag}" : $tag;
    $rawLog = run(['git', 'log', '--reverse', '--format=%H%x1f%s%x1f%b%x1e', $range]);
    $records = array_filter(explode("\x1e", $rawLog), static fn (string $record): bool => clean($record) !== '');

    return array_values(array_map(function (string $record): array {
        [$hash, $subject, $body] = array_pad(explode("\x1f", $record, 3), 3, '');

        return [
            'hash' => clean($hash),
            'subject' => clean($subject),
            'body' => clean($body),
        ];
    }, $records));
}

/**
 * @param  list<array{hash: string, subject: string, body: string}>  $commits
 * @return array<string, list<string>>
 */
function groupConventionalCommits(array $commits): array
{
    $groups = [
        'Features' => [],
        'Fixes' => [],
        'Performance' => [],
        'Refactoring' => [],
        'Documentation' => [],
        'Tests' => [],
        'CI and Build' => [],
        'Maintenance' => [],
        'Breaking Changes' => [],
        'Other Changes' => [],
    ];

    foreach ($commits as $commit) {
        if (str_starts_with($commit['subject'], 'Merge pull request ')) {
            continue;
        }

        if (! preg_match('/^(?<type>[a-z]+)(?:\((?<scope>[^)]+)\))?(?<breaking>!)?:\s*(?<description>.+)$/', $commit['subject'], $matches)) {
            $groups['Other Changes'][] = entry($commit['subject'], $commit['hash']);

            continue;
        }

        $description = $matches['description'];
        $scope = $matches['scope'] ?? '';

        if ($scope !== '') {
            $description = "**{$scope}:** {$description}";
        }

        $entry = entry($description, $commit['hash']);
        $groups[groupName($matches['type'])][] = $entry;

        if (($matches['breaking'] ?? '') === '!' || str_contains($commit['body'], 'BREAKING CHANGE')) {
            $groups['Breaking Changes'][] = $entry;
        }
    }

    return array_filter($groups, static fn (array $entries): bool => $entries !== []);
}

function groupName(string $type): string
{
    return match ($type) {
        'feat' => 'Features',
        'fix' => 'Fixes',
        'perf' => 'Performance',
        'refactor' => 'Refactoring',
        'docs' => 'Documentation',
        'test' => 'Tests',
        'ci', 'build' => 'CI and Build',
        'chore' => 'Maintenance',
        default => 'Other Changes',
    };
}

function entry(string $description, string $hash): string
{
    return sprintf('- %s (`%s`)', $description, shortHash($hash));
}

function shortHash(string $hash): string
{
    preg_match('/^(.{0,7})/s', $hash, $matches);

    return $matches[1] ?? $hash;
}

/**
 * @param  list<array{hash: string, subject: string, body: string}>  $commits
 * @return list<string>
 */
function mergedBranches(array $commits, string $repository): array
{
    $branches = [];

    foreach ($commits as $commit) {
        if (! preg_match('/^Merge pull request #(?<number>\d+) from (?<branch>.+)$/', $commit['subject'], $matches)) {
            continue;
        }

        $pullRequest = '#' . $matches['number'];

        if ($repository !== '') {
            $pullRequest = sprintf('[#%s](https://github.com/%s/pull/%s)', $matches['number'], $repository, $matches['number']);
        }

        $branches[] = sprintf('- `%s` via %s', $matches['branch'], $pullRequest);
    }

    return $branches;
}

/**
 * @param  array<string, list<string>>  $sections
 * @param  list<string>  $mergedBranches
 */
function renderChangelog(string $tag, string $date, ?string $previousTag, array $sections, array $mergedBranches): string
{
    $range = $previousTag ? "`{$previousTag}...{$tag}`" : "`{$tag}`";
    $lines = [
        "## {$tag} - {$date}",
        '',
        "Generated from Conventional Commits and merged branch metadata for {$range}.",
        '',
    ];

    if ($sections === [] && $mergedBranches === []) {
        $lines[] = '_No commits found for this tag._';
        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }

    foreach ($sections as $heading => $entries) {
        $lines[] = "### {$heading}";
        array_push($lines, ...$entries);
        $lines[] = '';
    }

    if ($mergedBranches !== []) {
        $lines[] = '### Merged Branches';
        array_push($lines, ...$mergedBranches);
        $lines[] = '';
    }

    return implode(PHP_EOL, $lines);
}
