<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\NotificationDigestQueue;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
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
    public function index(): Response
    {
        $this->handleAuthorization('viewAny', Role::class);

        $recipients = NotificationDigestQueue::query()
            ->with('user:id,name,email,profile_photo_path')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $items): array => $this->describeRecipient($items))
            ->sortByDesc('items_count')
            ->values();

        return $this->inertiaResponse('Admin/MailQueue', [
            'recipients' => $recipients,
            'canManage' => Auth::user()->isSuperAdmin(),
            'totals' => [
                'items' => $recipients->sum('items_count'),
                'recipients' => $recipients->count(),
            ],
        ]);
    }

    /**
     * One recipient's pending digest: who it is for, and the lines it will contain.
     *
     * @param  Collection<int, NotificationDigestQueue>  $items
     * @return array<string, mixed>
     */
    private function describeRecipient(Collection $items): array
    {
        $first = $items->first();

        return [
            'user_id' => $first->user_id,
            'user' => $first->user?->only(['id', 'name', 'email', 'profile_photo_path']),
            'items_count' => $items->count(),
            'oldest_at' => $items->min('created_at')?->toISOString(),
            'newest_at' => $items->max('created_at')?->toISOString(),
            'items' => $items->map(fn (NotificationDigestQueue $item): array => [
                'id' => $item->id,
                'category' => $item->category,
                'notification_class' => class_basename($item->notification_class),
                'title' => $item->data['title'] ?? null,
                'body' => $item->data['body'] ?? null,
                'url' => $item->data['url'] ?? null,
                'created_at' => $item->created_at?->toISOString(),
            ])->values()->all(),
        ];
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
