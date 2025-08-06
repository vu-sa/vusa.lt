<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

class UserNotificationsController extends AdminController
{
    public function index()
    {
        // get all notifications
        $notifications = auth()->user()->notifications;

        return $this->inertiaResponse('Admin/ShowNotifications', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead($id)
    {
        // mark notification as read
        auth()->user()->unreadNotifications->where('id', $id)->markAsRead();
    }

    public function markAllAsRead()
    {
        // mark all notifications as read
        auth()->user()->unreadNotifications->markAsRead();
    }
}
