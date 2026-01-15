<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
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
        $user->unreadNotifications()->where('id', $id)->update(['read_at' => now()]);

        return back();
    }

    public function markAllAsRead()
    {
        $user = User::query()->findOrFail(Auth::id());
        $user->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    public function destroy($id)
    {
        $user = User::query()->findOrFail(Auth::id());
        $user->notifications()->where('id', $id)->delete();

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
        }

        return back();
    }
}
