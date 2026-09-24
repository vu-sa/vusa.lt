<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\NotificationDigestQueue;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

/**
 * The pending side of the digest mailer.
 *
 * A queued email is not one row: `notification_digest_queue` holds one row per notification,
 * and ProcessNotificationDigests turns each user's rows into a single digest email. So the page
 * lists recipients, and an item is a line that email will contain.
 */
class MailQueueController extends AdminController
{
    public function index(\App\Http\Requests\IndexMailQueueRequest $request, \App\Actions\BuildMailQueuePage $builder): Response
    {
        $this->handleAuthorization('viewAny', Role::class);

        return $this->inertiaResponse('Admin/MailQueue', [
            'recipients' => $builder->execute($request),
            'canManage' => Auth::user()->isSuperAdmin(),
            'totals' => [
                'items' => NotificationDigestQueue::query()->count(),
                'recipients' => NotificationDigestQueue::query()->distinct('user_id')->count('user_id'),
            ],
        ]);
    }

    /**
     * Drop a single line from a pending digest.
     */
    public function destroy(NotificationDigestQueue $mailQueueItem): RedirectResponse
    {
        $this->authorizeManagement();

        $mailQueueItem->delete();

        return back()->with('success', __('messages.mail_queue.item_deleted'));
    }

    /**
     * Drop everything queued for one recipient — their pending digest never goes out.
     */
    public function destroyForUser(User $user): RedirectResponse
    {
        $this->authorizeManagement();

        $deleted = NotificationDigestQueue::query()->where('user_id', $user->id)->delete();

        return back()->with('success', __('messages.mail_queue.recipient_cleared', ['count' => $deleted]));
    }

    /**
     * Empty the queue. The escape hatch for a backlog that must not be delivered at all.
     */
    public function destroyAll(): RedirectResponse
    {
        $this->authorizeManagement();

        $deleted = NotificationDigestQueue::query()->delete();

        return back()->with('success', __('messages.mail_queue.cleared', ['count' => $deleted]));
    }

    /**
     * Reading the backlog is an admin question; discarding other people's mail is not.
     */
    private function authorizeManagement(): void
    {
        $this->handleAuthorization('viewAny', Role::class);

        abort_unless(Auth::user()->isSuperAdmin(), 403, 'Only super administrators may discard queued email.');
    }
}
