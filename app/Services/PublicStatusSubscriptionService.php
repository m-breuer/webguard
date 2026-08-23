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

    private function subscribeToStatusPage(StatusPage $statusPage, string $email): void
    {
        abort_unless($statusPage->is_public, 404);
        $subscription = StatusPageSubscription::query()->firstOrNew([
            'status_page_id' => $statusPage->id,
            'email' => Str::lower($email),
        ]);

        if ($subscription->exists && $subscription->isVerified()) {
            return;
        }

        $confirmationToken = Str::random(48);
        $subscription->forceFill([
            'confirmation_token_hash' => StatusPageSubscription::hashToken($confirmationToken),
            'unsubscribe_token' => Str::random(48),
            'verified_at' => null,
        ])->save();

        Mail::to($subscription->email)->send(new PublicStatusPageSubscriptionConfirmationMail($subscription, $confirmationToken));
    }

    private function subscribeToMonitoring(Monitoring $monitoring, string $email): void
    {
        abort_unless($monitoring->public_label_enabled, 404);
        $subscriber = StatusPageSubscriber::query()->firstOrNew([
            'monitoring_id' => $monitoring->id,
            'email' => Str::lower($email),
        ]);

        if ($subscriber->exists && $subscriber->isVerified()) {
            return;
        }

        $confirmationToken = Str::random(48);
        $subscriber->forceFill([
            'confirmation_token_hash' => StatusPageSubscriber::hashToken($confirmationToken),
            'unsubscribe_token' => Str::random(48),
            'verified_at' => null,
        ])->save();

        Mail::to($subscriber->email)->send(new StatusPageSubscriptionConfirmationMail($subscriber, $confirmationToken));
    }
}
