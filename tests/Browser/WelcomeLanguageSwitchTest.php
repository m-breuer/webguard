<?php

declare(strict_types=1);

it('allows guests to switch language on the welcome page', function () {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Browser test requires built Vite assets in public/build.');
    }

    $this->withVite();

    $deLocaleButtonSelector = 'form:has(input[name="locale"][value="de"]) > button[type="submit"]';

    $page = visit('/')->withLocale('en-US');

    $page->assertScript('document.documentElement.lang', 'en')
        ->click('#language-switch-guest')
        ->assertVisible($deLocaleButtonSelector)
        ->click($deLocaleButtonSelector)
        ->waitForEvent('domcontentloaded')
        ->assertPathIs('/')
        ->assertScript('document.documentElement.lang', 'de')
        ->assertAttribute('#language-switch-guest', 'title', 'Deutsch');
});
