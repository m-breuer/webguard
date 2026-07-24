<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ConsoleCommandRegistrationTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function commandProvider(): array
    {
        return [
            'aggregate daily monitoring results' => [
                'monitoring:aggregate-daily',
                'Aggregates daily monitoring results.',
            ],
            'archive monitoring responses' => [
                'monitoring:archive-responses',
                'Archives monitoring responses for a given date (defaults to yesterday) by moving them to a separate table and deleting them from the live table.',
            ],
            'evaluate heartbeat monitorings' => [
                'monitoring:evaluate-heartbeats',
                'Dispatches heartbeat evaluation to the dedicated heartbeat queue.',
            ],
            'purge soft-deleted monitorings' => [
                'monitoring:purge-soft-deleted',
                'Deletes all soft-deleted monitorings and their related data.',
            ],
            'dispatch status change notifications' => [
                'notifications:dispatch-status-changes',
                'Dispatch status change notifications to configured channels.',
            ],
            'prune demo notifications' => [
                'notifications:prune-demo',
                'Delete all notifications for demo users older than one week',
            ],
            'prune read notifications' => [
                'notifications:prune-read',
                'Delete read notifications that are older than one month.',
            ],
            'send ssl expiry warnings' => [
                'notifications:send-ssl-expiry-warnings',
                'Checks SSL certificates and domains and dispatches expiry notifications.',
            ],
            'send unread reminders' => [
                'notifications:remind-unread-weekly',
                'Sends email reminders to users with unread board notifications according to their profile settings.',
            ],
            'send server instance health alerts' => [
                'notifications:send-server-instance-health-alerts',
                'Sends admin alerts when scanner server instances become unreachable, remain unseen, or recover.',
            ],
            'send weekly monitoring digest' => [
                'notifications:send-weekly-monitoring-digest',
                'Sends weekly email summaries with uptime, incidents, downtime, and expiry warnings.',
            ],
            'create admin user' => [
                'user:create-admin',
                'Create a new admin user with a default password.',
            ],
        ];
    }

    public function test_attribute_backed_commands_are_registered_with_their_descriptions(): void
    {
        $commands = Artisan::all();

        foreach (self::commandProvider() as [$commandName, $description]) {
            $this->assertArrayHasKey($commandName, $commands);
            $this->assertSame($description, $commands[$commandName]->getDescription());
        }
    }

    public function test_attribute_backed_command_signatures_keep_arguments_and_options(): void
    {
        $commands = Artisan::all();

        $this->assertTrue($commands['monitoring:aggregate-daily']->getDefinition()->hasArgument('days'));
        $this->assertTrue($commands['notifications:send-weekly-monitoring-digest']->getDefinition()->hasOption('period-end'));
        $this->assertTrue($commands['user:create-admin']->getDefinition()->hasArgument('email'));
    }
}
