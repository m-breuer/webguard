<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\StatusPageSubscriptionConfirmationMail;
use App\Models\Monitoring;
use App\Models\StatusPageSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StatusPageSubscriberController extends Controller
{
    public function store(Monitoring $monitoring, Request $request): RedirectResponse
    {
        abort_unless($monitoring->public_label_enabled, 404);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = Str::lower((string) $validated['email']);
        $statusPageSubscriber = StatusPageSubscriber::query()->firstOrNew([
            'monitoring_id' => $monitoring->id,
            'email' => $email,
        ]);

        if (! $statusPageSubscriber->exists || ! $statusPageSubscriber->isVerified()) {
            $confirmationToken = Str::random(48);

            $statusPageSubscriber->forceFill([
                'confirmation_token_hash' => StatusPageSubscriber::hashToken($confirmationToken),
                'unsubscribe_token' => Str::random(48),
                'verified_at' => null,
            ])->save();

            Mail::to($statusPageSubscriber->email)->send(
                new StatusPageSubscriptionConfirmationMail($statusPageSubscriber, $confirmationToken)
            );
        }

        return to_route('public-status-pages.show', [
            'statusPage' => $monitoring,
            'subscription' => 'confirmation-sent',
        ]);
    }

    public function confirm(Monitoring $monitoring, string $token): RedirectResponse
    {
        abort_unless($monitoring->public_label_enabled, 404);

        $statusPageSubscriber = $monitoring->statusPageSubscribers()
            ->where('confirmation_token_hash', StatusPageSubscriber::hashToken($token))
            ->firstOrFail();

        $statusPageSubscriber->markVerified();

        return to_route('public-status-pages.show', [
            'statusPage' => $monitoring,
            'subscription' => 'confirmed',
        ]);
    }

    public function unsubscribe(Monitoring $monitoring, string $token): RedirectResponse
    {
        $monitoring->statusPageSubscribers()
            ->where('unsubscribe_token', $token)
            ->firstOrFail();

        return redirect()->away($this->subscriptionUrl($monitoring, $token));
    }

    public function destroy(Monitoring $monitoring, string $token, Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower((string) $request->string('email')),
        ]);

        $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                Rule::exists('status_page_subscribers', 'email')
                    ->where('monitoring_id', $monitoring->id)
                    ->where('unsubscribe_token', $token),
            ],
        ]);

        $monitoring->statusPageSubscribers()
            ->where('email', $request->string('email'))
            ->where('unsubscribe_token', $token)
            ->delete();

        return $monitoring->public_label_enabled
            ? to_route('public-status-pages.show', [
                'statusPage' => $monitoring,
                'subscription' => 'unsubscribed',
            ])
            : redirect('/');
    }

    private function subscriptionUrl(Monitoring $monitoring, string $token): string
    {
        return mb_rtrim((string) config('app.url'), '/')
            . '/status/' . rawurlencode((string) $monitoring->getRouteKey())
            . '/subscribers/unsubscribe/' . rawurlencode($token);
    }
}
