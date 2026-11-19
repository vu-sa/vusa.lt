<?php

namespace App\Http\Controllers\Admin;

use App\Actions\BuildMailQueuePage;
use App\Actions\BuildNotificationDigest;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexMailQueueRequest;
use App\Http\Requests\RenderMailPreviewRequest;
use App\Models\NotificationDigestQueue;
use App\Models\Role;
use App\Models\User;
use App\Services\MailPreview\MailPreviewRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
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
    #[Authorize('viewAny', Role::class)]
    public function index(IndexMailQueueRequest $request, BuildMailQueuePage $builder): Response
    {
        return Inertia::render('Admin/MailQueue', [
            'recipients' => $builder->execute($request),
            'canManage' => Auth::user()->isSuperAdmin(),
            'totals' => [
                'items' => NotificationDigestQueue::query()->count(),
                'recipients' => NotificationDigestQueue::query()->distinct('user_id')->count('user_id'),
            ],
        ]);
    }

    #[Authorize('viewAny', Role::class)]
    public function previewForUser(RenderMailPreviewRequest $request, User $user, BuildNotificationDigest $builder, MailPreviewRenderer $renderer): \Illuminate\Http\Response
    {
        $items = NotificationDigestQueue::query()->where('user_id', $user->id)->orderBy('created_at')->orderBy('id')->get();
        abort_if($items->isEmpty(), 404);

        return $renderer->response($renderer->safely(
            fn (): array => $renderer->render($builder->execute($user, $items)),
            $request->validated('locale', 'lt'),
        ));
    }

    /**
     * Drop a single line from a pending digest.
     */
    #[Authorize('viewAny', Role::class)]
    public function destroy(NotificationDigestQueue $mailQueueItem): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $mailQueueItem->delete();

        return back()->with('success', __('messages.mail_queue.item_deleted'));
    }

    /**
     * Drop everything queued for one recipient — their pending digest never goes out.
     */
    #[Authorize('viewAny', Role::class)]
    public function destroyForUser(User $user): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $deleted = NotificationDigestQueue::query()->where('user_id', $user->id)->delete();

        return back()->with('success', __('messages.mail_queue.recipient_cleared', ['count' => $deleted]));
    }

    /**
     * Empty the queue. The escape hatch for a backlog that must not be delivered at all.
     */
    #[Authorize('viewAny', Role::class)]
    public function destroyAll(): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $deleted = NotificationDigestQueue::query()->delete();

        return back()->with('success', __('messages.mail_queue.cleared', ['count' => $deleted]));
    }

    /**
     * Reading the backlog is an admin question; discarding other people's mail is not.
     */
    private function ensureSuperAdmin(): void
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403, 'Only super administrators may discard queued email.');
    }
}
