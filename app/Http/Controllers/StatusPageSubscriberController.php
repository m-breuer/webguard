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
use Illuminate\View\View;

class StatusPageSubscriberController extends Controller
{
    public function store(Monitoring $monitoring, Request $request): RedirectResponse
    {
        abort_unless($monitoring->public_label_enabled, 404);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = Str::lower((string) $validated['email']);
        $subscriber = StatusPageSubscriber::query()->firstOrNew([
            'monitoring_id' => $monitoring->id,
            'email' => $email,
        ]);

        if (! $subscriber->exists || ! $subscriber->isVerified()) {
            $confirmationToken = Str::random(48);

            $subscriber->forceFill([
                'confirmation_token_hash' => StatusPageSubscriber::hashToken($confirmationToken),
                'unsubscribe_token' => Str::random(48),
                'verified_at' => null,
            ])->save();

            Mail::to($subscriber->email)->send(
                new StatusPageSubscriptionConfirmationMail($subscriber, $confirmationToken)
            );
        }

        return to_route('public-label', $monitoring)
            ->with('status_page_subscription_success', __('monitoring.public_label.subscribe.confirmation_sent'));
    }

    public function confirm(Monitoring $monitoring, string $token): RedirectResponse
    {
        abort_unless($monitoring->public_label_enabled, 404);

        $subscriber = $monitoring->statusPageSubscribers()
            ->where('confirmation_token_hash', StatusPageSubscriber::hashToken($token))
            ->firstOrFail();

        $subscriber->markVerified();

        return to_route('public-label', $monitoring)
            ->with('status_page_subscription_success', __('monitoring.public_label.subscribe.confirmed'));
    }

    public function unsubscribe(Monitoring $monitoring, string $token): View
    {
        $subscriber = $monitoring->statusPageSubscribers()
            ->where('unsubscribe_token', $token)
            ->firstOrFail();

        return view('monitorings.status-page-unsubscribe', [
            'monitoring' => $monitoring,
            'token' => $token,
            'subscriber' => $subscriber,
        ]);
    }

    public function destroy(Monitoring $monitoring, string $token, Request $request): RedirectResponse
    {
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
            ->where('email', Str::lower((string) $request->string('email')))
            ->where('unsubscribe_token', $token)
            ->delete();

        $redirect = $monitoring->public_label_enabled
            ? to_route('public-label', $monitoring)
            : redirect('/');

        return $redirect->with('status_page_subscription_success', __('monitoring.public_label.subscribe.unsubscribed'));
    }
}
