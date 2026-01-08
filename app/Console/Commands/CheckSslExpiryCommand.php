<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\SslExpiryWarningMail;
use App\Models\MonitoringSslResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class CheckSslExpiryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:check-ssl-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for expiring SSL certificates and sends notification emails.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiringSslResults = MonitoringSslResult::query()->where('is_valid', true)
            ->where('expires_at', '<=', now()->addDays(7))
            ->with(['monitoring.user'])
            ->get();

        foreach ($expiringSslResults as $expiringSslResult) {
            $monitoring = $expiringSslResult->monitoring;
            $user = $monitoring->user;
            // Ensure the user exists and has an email
            if (! $user) {
                continue;
            }
            if (! $user->email) {
                continue;
            }

            // Prevent duplicate emails for the same issue more than once per day
            $cacheKey = 'ssl_expiry_notification_' . $expiringSslResult->id . '_' . now()->format('Y-m-d');

            if (Cache::missing($cacheKey)) {
                Mail::to($user->email)->send(new SslExpiryWarningMail($expiringSslResult, $monitoring));
                Cache::put($cacheKey, true, now()->addHours(23)); // Cache for almost 24 hours
                $this->info("Sent SSL expiry warning to {$user->email} for monitoring {$monitoring->name}.");
            } else {
                $this->info("Skipped sending SSL expiry warning to {$user->email} for monitoring {$monitoring->name} (already sent today).");
            }
        }

        $this->info('SSL expiry check completed.');

        return Command::SUCCESS;
    }
}
