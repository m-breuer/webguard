<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\PublicStatusPageSubscriptionConfirmationMail;
use App\Models\StatusPage;
use App\Models\StatusPageSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StatusPageSubscriptionController extends Controller
{
    public function store(StatusPage $statusPage, Request $request): RedirectResponse
    {
        abort_unless($statusPage->is_public, 404);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = Str::lower((string) $validated['email']);
        $subscription = StatusPageSubscription::query()->firstOrNew([
            'status_page_id' => $statusPage->id,
            'email' => $email,
        ]);

        if (! $subscription->exists || ! $subscription->isVerified()) {
            $confirmationToken = Str::random(48);

            $subscription->forceFill([
                'confirmation_token_hash' => StatusPageSubscription::hashToken($confirmationToken),
                'unsubscribe_token' => Str::random(48),
                'verified_at' => null,
            ])->save();

            Mail::to($subscription->email)->send(
                new PublicStatusPageSubscriptionConfirmationMail($subscription, $confirmationToken)
            );
        }

        return to_route('public-status-pages.show', $statusPage->slug)
            ->with('status_page_subscription_success', __('status_page.public.subscribe.confirmation_sent'));
    }

    public function confirm(StatusPage $statusPage, string $token): RedirectResponse
    {
        abort_unless($statusPage->is_public, 404);

        $subscription = $statusPage->subscriptions()
            ->where('confirmation_token_hash', StatusPageSubscription::hashToken($token))
            ->firstOrFail();

        $subscription->markVerified();

        return to_route('public-status-pages.show', $statusPage->slug)
            ->with('status_page_subscription_success', __('status_page.public.subscribe.confirmed'));
    }

    public function unsubscribe(StatusPage $statusPage, string $token): View
    {
        $subscription = $statusPage->subscriptions()
            ->where('unsubscribe_token', $token)
            ->firstOrFail();

        return view('status-pages.unsubscribe', [
            'statusPage' => $statusPage,
            'token' => $token,
            'subscription' => $subscription,
        ]);
    }

    public function destroy(StatusPage $statusPage, string $token, Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower((string) $request->string('email')),
        ]);

        $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                Rule::exists('status_page_subscriptions', 'email')
                    ->where('status_page_id', $statusPage->id)
                    ->where('unsubscribe_token', $token),
            ],
        ]);

        $statusPage->subscriptions()
            ->where('email', $request->string('email'))
            ->where('unsubscribe_token', $token)
            ->delete();

        $redirect = $statusPage->is_public
            ? to_route('public-status-pages.show', $statusPage->slug)
            : redirect('/');

        return $redirect->with('status_page_subscription_success', __('status_page.public.subscribe.unsubscribed'));
    }
}
