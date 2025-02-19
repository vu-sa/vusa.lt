<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class UserNotificationsController extends Controller
{
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
