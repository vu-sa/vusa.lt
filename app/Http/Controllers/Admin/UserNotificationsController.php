<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\NotificationDigestQueue;
use App\Models\User;
use Illuminate\Http\Request;
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
        $notification = $user->unreadNotifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
            NotificationDigestQueue::query()->where('user_id', $user->id)->where('notification_id', $notification->id)->delete();
        }

        return back();
    }

    public function markAllAsRead()
    {
        $user = User::query()->findOrFail(Auth::id());
        $user->unreadNotifications()->update(['read_at' => now()]);

        // All notifications are read, so clear the entire digest queue for this user
        NotificationDigestQueue::where('user_id', $user->id)->delete();

        return back();
    }

    public function destroy($id)
    {
        $user = User::query()->findOrFail(Auth::id());
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification) {
            NotificationDigestQueue::query()->where('user_id', $user->id)->where('notification_id', $notification->id)->delete();
            $notification->delete();
        }

        return back();
    }

    public function destroyAll(Request $request)
    {
        $user = User::query()->findOrFail(Auth::id());

        // If 'read_only' is passed, only delete read notifications
        if ($request->boolean('read_only')) {
            $user->readNotifications()->delete();
        } else {
            $user->notifications()->delete();
            NotificationDigestQueue::where('user_id', $user->id)->delete();
        }

        return back();
    }
}
