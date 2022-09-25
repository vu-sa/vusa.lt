<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;

class UserNotificationsController extends Controller
{
    public function markAsRead($id)
    {
        // mark notification as read
        auth()->user()->unreadNotifications->where('id', $id)->markAsRead();

        return redirect()->back();
    }
}
