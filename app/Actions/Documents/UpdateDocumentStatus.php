<?php

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Jobs\SyncDocumentFromSharePointJob;
use App\Models\Document;
use App\Models\Meeting;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Publish (queues the public link) or hide (revokes it, see DocumentObserver) SharePoint documents.
 *
 * Every transition re-reads the row under a lock: the link sync stores its permission under the same
 * lock, so a hide always sees the permission it has to revoke.
 */
class UpdateDocumentStatus
{
    /**
     * @param  Collection<int, Document>  $documents  refreshed in place
     * @return Collection<int, Document> the documents whose status changed
     */
    public static function execute(Collection $documents, DocumentStatus $status, User $user): Collection
    {
        return $documents->filter(fn (Document $document): bool => self::transition($document, $user, fn (): DocumentStatus => $status))->values();
    }

    /**
     * `execute()` plus the message for the person who asked. Hiding a file that was never shown takes
     * nothing off vusa.lt, so it is not announced as if it did.
     *
     * @param  Collection<int, Document>  $documents  refreshed in place
     */
    public static function executeWithMessage(Collection $documents, DocumentStatus $status, User $user): string
    {
        $wasShown = $documents->contains(fn (Document $document): bool => $document->isPublished());
        $changed = self::execute($documents, $status, $user)->count();

        return match (true) {
            $status === DocumentStatus::Published => __('messages.document.published', ['count' => $changed]),
            $wasShown => __('messages.document.hidden', ['count' => $changed]),
            default => __('messages.document.hidden_unpublished', ['count' => $changed]),
        };
    }

    /**
     * Linking a file nobody has decided on yet is that decision; a hidden one stays hidden.
     *
     * @throws ValidationException when another meeting already holds the file; the request checked it
     *                             without the lock, so a concurrent link may have won meanwhile.
     */
    public static function linkToMeeting(Document $document, Meeting $meeting, User $user): void
    {
        self::transition($document, $user, function (Document $current) use ($meeting): DocumentStatus {
            if ($current->meeting_id !== null && $current->meeting_id !== $meeting->id) {
                throw ValidationException::withMessages(['document_id' => __('messages.meeting.document_linked_elsewhere')]);
            }

            $current->meeting_id = $meeting->id;

            return $current->status === DocumentStatus::Pending ? DocumentStatus::Published : $current->status;
        });
    }

    /**
     * @param  Closure(Document): DocumentStatus  $target  may also change other attributes of the locked row
     */
    private static function transition(Document $document, User $user, Closure $target): bool
    {
        $changed = DB::transaction(function () use ($document, $user, $target): bool {
            $current = Document::query()->whereKey($document->getKey())->lockForUpdate()->firstOrFail();
            $status = $target($current);
            $changed = $current->status !== $status;

            if ($changed) {
                $current->status = $status;

                if ($status === DocumentStatus::Published) {
                    $current->published_at = now();
                    $current->published_by = $user->id;
                    // Until the queued sync reports back, the missing link is expected, not a failure.
                    $current->sync_status = 'pending';
                }
            }

            $current->save();

            if ($changed && $status === DocumentStatus::Published) {
                SyncDocumentFromSharePointJob::dispatch($current, force: true)->afterCommit();
            }

            return $changed;
        });

        $document->refresh();

        return $changed;
    }
}
