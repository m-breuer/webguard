<?php

declare(strict_types=1);

namespace App\Http\View\Composers;

use App\Services\NotificationBoardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationComposer
{
    public function __construct(private readonly NotificationBoardService $notificationBoardService) {}

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
                $unreadNotificationsCount = $this->notificationBoardService->getUnreadNotificationCount();
                request()->attributes->set('unread_notifications_count', $unreadNotificationsCount);
            }
        }

        $view->with('unreadNotificationsCount', $unreadNotificationsCount);
    }
}
