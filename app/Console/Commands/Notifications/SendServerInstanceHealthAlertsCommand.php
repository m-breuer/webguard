<?php

declare(strict_types=1);

namespace App\Console\Commands\Notifications;

use App\Enums\UserRole;
use App\Mail\ServerInstanceHealthAlertMail;
use App\Models\ServerInstance;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

#[Description('Sends admin alerts when scanner server instances become stale, remain unseen, or recover.')]
#[Signature('notifications:send-server-instance-health-alerts')]
class SendServerInstanceHealthAlertsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $admins = $this->verifiedAdmins();

        if ($admins->isEmpty()) {
            return Command::SUCCESS;
        }

        ServerInstance::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->each(function (ServerInstance $serverInstance) use ($admins): void {
                $healthStatus = $serverInstance->healthStatus();

                if (! $this->shouldAlert($serverInstance, $healthStatus)) {
                    return;
                }

                $this->notifyAdmins($admins, $serverInstance, $healthStatus);

                $serverInstance->forceFill([
                    'last_health_alert_status' => $healthStatus,
                    'last_health_alerted_at' => Date::now(),
                ])->saveQuietly();
            });

        return Command::SUCCESS;
    }

    /**
     * @return Collection<int, User>
     */
    private function verifiedAdmins(): Collection
    {
        return User::query()
            ->where('role', UserRole::ADMIN)
            ->whereNotNull('email_verified_at')
            ->whereNotNull('email')
            ->orderBy('email')
            ->get();
    }

    private function shouldAlert(ServerInstance $serverInstance, string $healthStatus): bool
    {
        if ($healthStatus === 'healthy') {
            return in_array($serverInstance->last_health_alert_status, ['stale', 'never_seen'], true);
        }

        if ($healthStatus === 'stale') {
            return $serverInstance->last_health_alert_status !== 'stale';
        }

        if ($healthStatus === 'never_seen') {
            if ($serverInstance->last_health_alert_status === 'never_seen') {
                return false;
            }

            $alertAfterMinutes = max(1, (int) config('monitoring.instance_never_seen_alert_after_minutes', 15));

            return $serverInstance->created_at?->lessThanOrEqualTo(Date::now()->subMinutes($alertAfterMinutes)) ?? false;
        }

        return false;
    }

    /**
     * @param  Collection<int, User>  $admins
     */
    private function notifyAdmins(Collection $admins, ServerInstance $serverInstance, string $healthStatus): void
    {
        foreach ($admins as $admin) {
            if (blank($admin->email)) {
                continue;
            }

            try {
                Mail::to($admin->email)->send(
                    (new ServerInstanceHealthAlertMail($serverInstance, $healthStatus, $admin))
                        ->locale($admin->locale ?? config('app.locale'))
                );
            } catch (Throwable $throwable) {
                Log::error('Failed to send server instance health alert.', [
                    'server_instance_id' => $serverInstance->id,
                    'server_instance_code' => $serverInstance->code,
                    'health_status' => $healthStatus,
                    'admin_id' => $admin->id,
                    'exception' => $throwable->getMessage(),
                ]);
            }
        }
    }
}
