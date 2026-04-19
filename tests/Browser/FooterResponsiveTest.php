<?php

declare(strict_types=1);

it('keeps the footer within the viewport on mobile widths', function () {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Browser test requires built Vite assets in public/build.');
    }

    $this->withVite();

    visit('/')
        ->resize(320, 640)
        ->assertScript(<<<'JS'
function () {
    const footer = document.querySelector('footer');
    const footerNavigation = document.querySelector('footer nav ul');

    if (! footer || ! footerNavigation) {
        return false;
    }

    return footer.scrollWidth <= footer.clientWidth
        && footerNavigation.scrollWidth <= footerNavigation.clientWidth;
}
JS, true);
});
