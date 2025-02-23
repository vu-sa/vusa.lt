<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class UserNotificationsController extends Controller
{
    public function index()
    {
        // get all notifications
        $notifications = auth()->user()->notifications;

        return Inertia::render('Admin/ShowNotifications', [
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
