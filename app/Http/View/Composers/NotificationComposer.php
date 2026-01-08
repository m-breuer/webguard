<?php

declare(strict_types=1);

namespace App\Http\View\Composers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationComposer
{
    /**
     * Bind data to the view.
     *
     * @return void
     */
    public function compose(View $view)
    {
        $unreadNotificationsCount = 0;

        if (Auth::check()) {
            $unreadNotificationsCount = Auth::user()->unreadNotifications()->count();
        }

        $view->with('unreadNotificationsCount', $unreadNotificationsCount);
    }
}
