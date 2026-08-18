<?php

declare(strict_types=1);

use App\Models\Package;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('displays status pages as responsive management cards', function (): void {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Browser test requires built Vite assets in public/build.');
    }

    $this->withVite();

    $package = Package::factory()->create();
    $user = User::factory()->create([
        'package_id' => $package->id,
        'password' => Hash::make('password'),
    ]);
    StatusPage::query()->create([
        'user_id' => $user->id,
        'name' => 'Customer status',
        'description' => 'Service availability for customers.',
        'is_public' => true,
    ]);

    $webpage = visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('form[action$="/login"] button[type="submit"]')
        ->navigate('/status-pages');

    foreach ([[1280, 800], [768, 1024], [390, 844]] as $iteration => [$width, $height]) {
        if ($iteration > 0) {
            $webpage->navigate('/status-pages');
        }

        $webpage
            ->resize($width, $height)
            ->assertCount('[data-status-page-card]', 1)
            ->assertCount('[data-status-page-actions] > *', 3)
            ->assertScript(<<<'JS'
function () {
    const card = document.querySelector('[data-status-page-card]');
    const actions = document.querySelector('[data-status-page-actions]');

    if (!card || !actions) {
        return false;
    }

    const cardBounds = card.getBoundingClientRect();
    const actionsBounds = actions.getBoundingClientRect();
    const actionRows = new Set([...actions.children].map((action) => action.getBoundingClientRect().top));

    return document.body.scrollWidth <= document.documentElement.clientWidth
        && actionsBounds.right <= cardBounds.right + 1
        && actionRows.size === 1;
}
JS, true)
            ->assertNoJavaScriptErrors();
    }
});
