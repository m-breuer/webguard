<?php

declare(strict_types=1);

namespace App\Console\Commands\Notifications;

use App\Mail\WeeklyMonitoringDigestMail;
use App\Models\User;
use App\Services\WeeklyMonitoringDigestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendWeeklyMonitoringDigestCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notifications:send-weekly-monitoring-digest {--period-end= : Final day to include in the weekly digest.}';

    /**
     * @var string
     */
    protected $description = 'Sends weekly email summaries with uptime, incidents, downtime, and expiry warnings.';

    public function __construct(private readonly WeeklyMonitoringDigestService $weeklyMonitoringDigestService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $periodEndOption = $this->option('period-end');
        $periodEnd = is_string($periodEndOption) && $periodEndOption !== ''
            ? Date::parse($periodEndOption)->endOfDay()
            : null;

        User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where('monitoring_digest_enabled', true)
            ->whereHas('monitorings', fn ($builder) => $builder->active())
            ->chunkById(100, function ($users) use ($periodEnd): void {
                foreach ($users as $user) {
                    $frequency = $user->monitoring_digest_frequency ?: 'weekly';

                    if (! $this->isDue($frequency)) {
                        continue;
                    }

                    $digest = $this->weeklyMonitoringDigestService->buildForUser($user, $periodEnd, $frequency);

                    if (($digest['overview']['monitorings_count'] ?? 0) < 1) {
                        continue;
                    }

                    try {
                        Mail::to($user->email)->send(
                            (new WeeklyMonitoringDigestMail($digest, $user))
                                ->locale($user->locale ?? config('app.locale'))
                        );
                    } catch (Throwable $throwable) {
                        Log::error('Failed to send weekly monitoring digest.', [
                            'user_id' => $user->id,
                            'exception' => $throwable->getMessage(),
                        ]);
                    }
                }
            });

        return Command::SUCCESS;
    }

    private function isDue(string $frequency): bool
    {
        return match ($frequency) {
            'daily' => true,
            'monthly' => Date::now()->day === 1,
            default => Date::now()->isMonday(),
        };
    }
}
