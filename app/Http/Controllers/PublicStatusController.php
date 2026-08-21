<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StatusPage;
use App\Support\PublicStatusResourceResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicStatusController extends Controller
{
    public function __construct(
        private readonly PublicStatusResourceResolver $publicStatusResourceResolver,
        private readonly PublicLabelController $publicLabelController,
        private readonly PublicStatusPageController $publicStatusPageController
    ) {}

    public function __invoke(string $statusPage, Request $request): View
    {
        $publicStatusResource = $this->publicStatusResourceResolver->resolve($statusPage);

        if ($publicStatusResource instanceof StatusPage) {
            return ($this->publicStatusPageController)($publicStatusResource);
        }

        return ($this->publicLabelController)($publicStatusResource, $request);
    }
}
