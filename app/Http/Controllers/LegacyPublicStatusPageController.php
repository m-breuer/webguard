<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StatusPage;
use App\Models\StatusPageSubscription;
use Illuminate\Http\RedirectResponse;

class LegacyPublicStatusPageController extends Controller
{
    public function show(string $statusPageSlug): RedirectResponse
    {
        return redirect()->route(
            'public-status-pages.show',
            $this->resolvePublicStatusPage($statusPageSlug),
            301
        );
    }

    public function store(string $statusPageSlug): RedirectResponse
    {
        return redirect()->route(
            'public-status-pages.subscribers.store',
            $this->resolvePublicStatusPage($statusPageSlug),
            307
        );
    }

    public function confirm(string $statusPageSlug, string $token): RedirectResponse
    {
        return redirect()->route('public-status-pages.subscribers.confirm', [
            'statusPage' => $this->resolveConfirmationStatusPage($statusPageSlug, $token),
            'token' => $token,
        ]);
    }

    public function unsubscribe(string $statusPageSlug, string $token): RedirectResponse
    {
        return redirect()->route('public-status-pages.subscribers.unsubscribe', [
            'statusPage' => $this->resolveUnsubscribeStatusPage($statusPageSlug, $token),
            'token' => $token,
        ]);
    }

    public function destroy(string $statusPageSlug, string $token): RedirectResponse
    {
        return redirect()->route('public-status-pages.subscribers.destroy', [
            'statusPage' => $this->resolveUnsubscribeStatusPage($statusPageSlug, $token),
            'token' => $token,
        ], 307);
    }

    private function resolvePublicStatusPage(string $statusPageSlug): StatusPage
    {
        return StatusPage::query()
            ->where('slug', $statusPageSlug)
            ->where('is_public', true)
            ->firstOrFail();
    }

    private function resolveConfirmationStatusPage(string $statusPageSlug, string $token): StatusPage
    {
        $statusPage = $this->resolvePublicStatusPage($statusPageSlug);

        abort_unless($statusPage->subscriptions()
            ->where('confirmation_token_hash', StatusPageSubscription::hashToken($token))
            ->exists(), 404);

        return $statusPage;
    }

    private function resolveUnsubscribeStatusPage(string $statusPageSlug, string $token): StatusPage
    {
        $statusPage = StatusPage::query()->where('slug', $statusPageSlug)->firstOrFail();

        abort_unless($statusPage->subscriptions()->where('unsubscribe_token', $token)->exists(), 404);

        return $statusPage;
    }
}
