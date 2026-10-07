<?php

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Unpublished SharePoint files a meeting could link: its institutions' or their padaliniai's, not yet
 * linked anywhere, those dated on the meeting day first — the protokolas just uploaded is the one
 * the user came for.
 */
class PendingMeetingDocuments
{
    /**
     * @return Builder<Document>
     */
    public static function query(Meeting $meeting, User $user): Builder
    {
        return Document::query()
            ->manageableBy($user)
            ->where('status', DocumentStatus::Pending)
            ->whereNull('meeting_id')
            ->whereNull('removed_from_sharepoint_at')
            ->linkableTo($meeting)
            ->with('institution.tenant:id,shortname')
            ->orderByRaw('CASE WHEN document_date = ? THEN 0 ELSE 1 END', [$meeting->start_time->toDateString()])
            ->orderByDesc('sharepoint_modified_at');
    }
}
