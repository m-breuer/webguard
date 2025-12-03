<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Class GuestLayout
 *
 * Layout component for unauthenticated guest pages.
 * Renders the `layouts.guest` Blade template.
 */
class GuestLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
