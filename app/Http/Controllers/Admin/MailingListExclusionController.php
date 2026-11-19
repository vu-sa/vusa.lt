<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\MailingLists\StoreMailingListExclusionRequest;
use App\Listeners\QueueMailingListSync;
use App\Models\MailingList;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class MailingListExclusionController extends AdminController
{
    #[Authorize('update', 'mailingList')]
    public function store(StoreMailingListExclusionRequest $request, MailingList $mailingList): RedirectResponse
    {
        try {
            $mailingList->withSyncLock(fn () => $mailingList->exclusions()->create([
                ...$request->safe()->only(['email', 'note']),
                'created_by' => $request->user()->id,
            ]));
        } catch (LockTimeoutException) {
            return back()->with('error', __('mailing_lists.messages.update_failed'));
        }

        QueueMailingListSync::queueSync();

        return back()->with('success', __('mailing_lists.messages.exclusion_added'));
    }

    #[Authorize('update', 'mailingList')]
    public function destroy(MailingList $mailingList, string $exclusion): RedirectResponse
    {
        try {
            $mailingList->withSyncLock(function () use ($mailingList, $exclusion): void {
                $model = $mailingList->exclusions()->find($exclusion);
                abort_if($model === null, 404);

                $model->delete();
            });
        } catch (LockTimeoutException) {
            return back()->with('error', __('mailing_lists.messages.update_failed'));
        }
        QueueMailingListSync::queueSync();

        return back()->with('success', __('mailing_lists.messages.exclusion_removed'));
    }
}
