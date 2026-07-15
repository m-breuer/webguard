<?php

declare(strict_types=1);

it('keeps the public shell responsive and keyboard reachable', function () {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Browser test requires built Vite assets in public/build.');
    }

    $this->withVite();

    foreach ([[1280, 800], [390, 844]] as [$width, $height]) {
        visit('/login')
            ->resize($width, $height)
            ->assertScript(<<<'JS'
function () {
    const documentWidth = document.documentElement.clientWidth;
    const firstFocusable = document.querySelector('a, button, input, select, textarea');
    const submitButton = document.querySelector('button[type="submit"]');

    firstFocusable?.focus();

    return document.body.scrollWidth <= documentWidth
        && firstFocusable !== null
        && document.activeElement === firstFocusable
        && firstFocusable.matches(':focus')
        && submitButton !== null
        && (submitButton.textContent?.trim() || submitButton.getAttribute('aria-label'));
}
JS, true);
    }
});
