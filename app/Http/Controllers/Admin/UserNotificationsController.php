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
            $this->removeMatchingDigestEntries($user, $notification->data);
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
            $this->removeMatchingDigestEntries($user, $notification->data);
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

    /**
     * Remove a single digest queue entry that matches a notification's data.
     *
     * Matches on title + body + url to avoid false positives when multiple
     * notifications share the same title and URL (e.g. "Nauja užduotis" all
     * pointing to /mano/tasks — the body contains the specific task name).
     *
     * @param  array<string, mixed>  $notificationData
     */
    private function removeMatchingDigestEntries(User $user, array $notificationData): void
    {
        $query = NotificationDigestQueue::where('user_id', $user->id);

        if (isset($notificationData['title'])) {
            $query->whereJsonContains('data->title', $notificationData['title']);
        }

        if (isset($notificationData['body'])) {
            $query->whereJsonContains('data->body', $notificationData['body']);
        }

        if (isset($notificationData['url'])) {
            $query->whereJsonContains('data->url', $notificationData['url']);
        }

        // Delete only one matching entry to avoid removing digest items
        // for other unread notifications with similar data
        $match = $query->first();
        $match?->delete();
    }
}
