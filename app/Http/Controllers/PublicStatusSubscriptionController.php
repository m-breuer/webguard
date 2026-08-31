<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StatusPage;
use App\Support\PublicStatusResourceResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicStatusSubscriptionController extends Controller
{
    public function __construct(
        private readonly PublicStatusResourceResolver $publicStatusResourceResolver,
        private readonly StatusPageSubscriberController $statusPageSubscriberController,
        private readonly StatusPageSubscriptionController $statusPageSubscriptionController
    ) {}

    public function store(string $statusPage, Request $request): RedirectResponse
    {
        $publicStatusResource = $this->publicStatusResourceResolver->resolve($statusPage);

        if ($publicStatusResource instanceof StatusPage) {
            return $this->statusPageSubscriptionController->store($publicStatusResource, $request);
        }

        return $this->statusPageSubscriberController->store($publicStatusResource, $request);
    }

    public function confirm(string $statusPage, string $token): RedirectResponse
    {
        $publicStatusResource = $this->publicStatusResourceResolver->resolve($statusPage);

        if ($publicStatusResource instanceof StatusPage) {
            return $this->statusPageSubscriptionController->confirm($publicStatusResource, $token);
        }

        return $this->statusPageSubscriberController->confirm($publicStatusResource, $token);
    }

    public function unsubscribe(string $statusPage, string $token): RedirectResponse
    {
        $publicStatusResource = $this->publicStatusResourceResolver->resolve($statusPage);

        if ($publicStatusResource instanceof StatusPage) {
            return $this->statusPageSubscriptionController->unsubscribe($publicStatusResource, $token);
        }

        return $this->statusPageSubscriberController->unsubscribe($publicStatusResource, $token);
    }

    public function destroy(string $statusPage, string $token, Request $request): RedirectResponse
    {
        $publicStatusResource = $this->publicStatusResourceResolver->resolve($statusPage);

        if ($publicStatusResource instanceof StatusPage) {
            return $this->statusPageSubscriptionController->destroy($publicStatusResource, $token, $request);
        }

        return $this->statusPageSubscriberController->destroy($publicStatusResource, $token, $request);
    }
}
