<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserNotificationsController extends AdminController
{
    public function index()
    {
        // get all notifications
        $user = User::query()->findOrFail(Auth::id());
        $notifications = $user->notifications;

        return $this->inertiaResponse('Admin/ShowNotifications', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead($id)
    {
        $user = User::query()->findOrFail(Auth::id());
        $user->unreadNotifications()->where('id', $id);
    }

    public function markAllAsRead()
    {
        $user = User::query()->findOrFail(Auth::id());
        $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
