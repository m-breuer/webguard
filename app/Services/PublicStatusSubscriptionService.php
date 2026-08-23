<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\PublicStatusPageSubscriptionConfirmationMail;
use App\Mail\StatusPageSubscriptionConfirmationMail;
use App\Models\Monitoring;
use App\Models\StatusPage;
use App\Models\StatusPageSubscriber;
use App\Models\StatusPageSubscription;
use App\Support\PublicStatusResourceResolver;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PublicStatusSubscriptionService
{
    public function __construct(private readonly PublicStatusResourceResolver $publicStatusResourceResolver) {}

    public function subscribe(string $identifier, string $email): void
    {
        $resource = $this->publicStatusResourceResolver->resolve($identifier);

        if ($resource instanceof StatusPage) {
            $this->subscribeToStatusPage($resource, $email);

            return;
        }

        $this->subscribeToMonitoring($resource, $email);
    }

    public function unsubscribe(string $identifier, string $email, string $token): bool
    {
        $statusPage = StatusPage::query()->find($identifier);

        if ($statusPage instanceof StatusPage) {
            $statusPage->subscriptions()
                ->where('email', Str::lower($email))
                ->where('unsubscribe_token', $token)
                ->firstOrFail()
                ->delete();

            return $statusPage->is_public;
        }

        $monitoring = Monitoring::query()->findOrFail($identifier);

        $monitoring->statusPageSubscribers()
            ->where('email', Str::lower($email))
            ->where('unsubscribe_token', $token)
            ->firstOrFail()
            ->delete();

        return $monitoring->public_label_enabled;
    }

    private function subscribeToStatusPage(StatusPage $statusPage, string $email): void
    {
        abort_unless($statusPage->is_public, 404);
        $statusPageSubscription = StatusPageSubscription::query()->firstOrNew([
            'status_page_id' => $statusPage->id,
            'email' => Str::lower($email),
        ]);

        if ($statusPageSubscription->exists && $statusPageSubscription->isVerified()) {
            return;
        }

        $confirmationToken = Str::random(48);
        $statusPageSubscription->forceFill([
            'confirmation_token_hash' => StatusPageSubscription::hashToken($confirmationToken),
            'unsubscribe_token' => Str::random(48),
            'verified_at' => null,
        ])->save();

        Mail::to($statusPageSubscription->email)->send(new PublicStatusPageSubscriptionConfirmationMail($statusPageSubscription, $confirmationToken));
    }

    private function subscribeToMonitoring(Monitoring $monitoring, string $email): void
    {
        abort_unless($monitoring->public_label_enabled, 404);
        $statusPageSubscriber = StatusPageSubscriber::query()->firstOrNew([
            'monitoring_id' => $monitoring->id,
            'email' => Str::lower($email),
        ]);

        if ($statusPageSubscriber->exists && $statusPageSubscriber->isVerified()) {
            return;
        }

        $confirmationToken = Str::random(48);
        $statusPageSubscriber->forceFill([
            'confirmation_token_hash' => StatusPageSubscriber::hashToken($confirmationToken),
            'unsubscribe_token' => Str::random(48),
            'verified_at' => null,
        ])->save();

        Mail::to($statusPageSubscriber->email)->send(new StatusPageSubscriptionConfirmationMail($statusPageSubscriber, $confirmationToken));
    }
}
