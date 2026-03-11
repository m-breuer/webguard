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
            if (request()->attributes->has('unread_notifications_count')) {
                $unreadNotificationsCount = (int) request()->attributes->get('unread_notifications_count');
            } else {
                $unreadNotificationsCount = Auth::user()->unreadNotifications()->count();
                request()->attributes->set('unread_notifications_count', $unreadNotificationsCount);
            }
        }

        $view->with('unreadNotificationsCount', $unreadNotificationsCount);
    }
}
