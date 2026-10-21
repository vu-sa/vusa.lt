<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MailingListSourceType;
use App\Http\Controllers\AdminController;
use App\Http\Requests\MailingLists\StoreMailingListSourceRequest;
use App\Listeners\QueueMailingListSync;
use App\Models\MailingList;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;

/**
 * Sources and exclusions have no permissions of their own; they are edits to the list.
 */
class MailingListSourceController extends AdminController
{
    public function store(StoreMailingListSourceRequest $request, MailingList $mailingList): RedirectResponse
    {
        $type = MailingListSourceType::from($request->validated('type'));

        try {
            $mailingList->withSyncLock(fn () => $mailingList->sources()->create([
                ...$request->safe()->only(['type', 'address_mode', 'note', 'expires_on']),
                'source_id' => $type === MailingListSourceType::Email ? null : (string) $request->validated('source_id'),
                'email' => $type === MailingListSourceType::Email ? $request->validated('email') : null,
                'display_name' => $type === MailingListSourceType::Email ? $request->validated('display_name') : null,
                'filters' => [
                    ...($this->filters($type, (array) $request->validated('filters', [])) ?? []),
                    'authorized_tenant_ids' => $request->authorizedTenantIds(),
                ],
                'created_by' => $request->user()->id,
            ]));
        } catch (LockTimeoutException) {
            return back()->with('error', __('mailing_lists.messages.update_failed'));
        }

        QueueMailingListSync::queueSync();

        return back()->with('success', __('mailing_lists.messages.source_added'));
    }

    public function destroy(MailingList $mailingList, string $source): RedirectResponse
    {
        $this->handleAuthorization('update', $mailingList);

        // Resolved through the list so a crafted id cannot reach another list's source.
        try {
            $mailingList->withSyncLock(function () use ($mailingList, $source): void {
                $model = $mailingList->sources()->find($source);
                abort_if($model === null, 404);

                $model->delete();
            });
        } catch (LockTimeoutException) {
            return back()->with('error', __('mailing_lists.messages.update_failed'));
        }
        QueueMailingListSync::queueSync();

        return back()->with('success', __('mailing_lists.messages.source_removed'));
    }

    /**
     * Only the filters that mean something for the source type are kept.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>|null
     */
    private function filters(MailingListSourceType $type, array $filters): ?array
    {
        $allowed = match ($type) {
            MailingListSourceType::DutyType => ['tenant_scope', 'tenant_ids', 'include_subtypes'],
            MailingListSourceType::Responsibility => ['tenant_scope', 'tenant_ids'],
            MailingListSourceType::Institution => ['duty_type_ids', 'include_subtypes'],
            MailingListSourceType::InstitutionType => ['tenant_scope', 'tenant_ids', 'include_subtypes', 'exclude_institution_type_ids', 'duty_type_ids'],
            default => [],
        };

        $kept = array_filter(
            array_intersect_key($filters, array_flip($allowed)),
            fn ($value) => $value !== null && $value !== [],
        );

        if (($kept['tenant_scope'] ?? null) !== 'selected') {
            unset($kept['tenant_ids']);
        }

        return $kept === [] ? null : $kept;
    }
}
