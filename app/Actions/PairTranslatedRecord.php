<?php

namespace App\Actions;

use App\Models\News;
use App\Models\Page;
use Illuminate\Support\Facades\DB;

/**
 * Maintains the `other_lang_id` pairing between the two language versions of a page
 * or a news article.
 *
 * `pages.other_lang_id` and `news.other_lang_id` each carry a plain UNIQUE index that
 * predates soft deletes, so exactly one row may hold a given value — and a *trashed*
 * row keeps holding it, invisibly, forever. Every scoped query the controllers used
 * to run ("who currently points at this id?") skips trashed rows, so the stale holder
 * was never released and the next write hit:
 *
 *     SQLSTATE[23000] ... Duplicate entry '722' for key 'pages_other_lang_id_unique'
 *
 * The pointer carries no information of its own, so reclaiming it from a trashed row
 * loses nothing. This action therefore always releases both sides before claiming
 * them, counting trashed rows as holders.
 */
class PairTranslatedRecord
{
    /**
     * Point $record at $counterpartId and point that counterpart back, releasing
     * whichever rows currently claim either side.
     *
     * Passing null unpairs $record and clears its counterpart's back-reference.
     *
     * @param  Page|News  $record  the record being edited
     * @param  int|string|null  $counterpartId  id of the other-language record, or null to unpair
     *
     * @throws \InvalidArgumentException when the counterpart does not exist, is trashed, or is the record itself
     */
    public static function execute(Page|News $record, int|string|null $counterpartId): void
    {
        $counterpartId = $counterpartId === null || $counterpartId === '' ? null : (int) $counterpartId;

        if ($counterpartId !== null && (int) $record->getKey() === $counterpartId) {
            throw new \InvalidArgumentException('A record cannot be paired with itself.');
        }

        DB::transaction(function () use ($record, $counterpartId): void {
            // Releases must be committed before the claims: the unique index rejects the
            // write the moment two rows would hold the same value, even mid-transaction.
            self::release($record, $record->getKey(), except: $counterpartId);

            if ($counterpartId === null) {
                self::assign($record, null);

                return;
            }

            $counterpart = $record::withTrashed()->find($counterpartId);

            if ($counterpart === null) {
                throw new \InvalidArgumentException("Counterpart {$counterpartId} does not exist.");
            }

            if ($counterpart->trashed()) {
                throw new \InvalidArgumentException("Counterpart {$counterpartId} is deleted.");
            }

            self::release($record, $counterpartId, except: $record->getKey());

            self::assign($record, $counterpartId);
            self::assign($counterpart, $record->getKey());
        });
    }

    /**
     * Clear the counterpart's back-reference when a record is soft-deleted, so the
     * surviving language version stops offering a language switch to a deleted page.
     *
     * The deleted record keeps its own pointer, which is what lets {@see repair()}
     * restore the pairing if it comes back.
     */
    public static function releaseCounterpart(Page|News $record): void
    {
        self::release($record, $record->getKey(), except: null);
    }

    /**
     * Re-establish the pairing of a restored record, but only if its remembered
     * counterpart is still live and still unclaimed. A record whose partner has since
     * been paired with something else simply comes back unpaired.
     */
    public static function repair(Page|News $record): void
    {
        $counterpartId = $record->other_lang_id;

        if ($counterpartId === null) {
            return;
        }

        $counterpart = $record::query()->find($counterpartId);

        if ($counterpart === null) {
            self::assign($record, null);

            return;
        }

        if ($counterpart->other_lang_id !== null && (int) $counterpart->other_lang_id !== (int) $record->getKey()) {
            self::assign($record, null);

            return;
        }

        self::assign($counterpart, $record->getKey());
    }

    /**
     * Null the `other_lang_id` of every row pointing at $targetId.
     *
     * Iterates rather than issuing one mass UPDATE so each row's `saved` hook runs —
     * that hook flushes the page/news cache, and without it the surviving counterpart
     * keeps serving a cached language-switch link to a deleted record for up to an hour.
     *
     * @param  Page|News  $record  used only for its class, to pick the right table
     */
    private static function release(Page|News $record, int|string $targetId, int|string|null $except): void
    {
        $query = $record::withTrashed()->where('other_lang_id', $targetId);

        if ($except !== null) {
            $query->whereKeyNot($except);
        }

        $query->get()->each(fn (Page|News $holder) => self::assign($holder, null));
    }

    private static function assign(Page|News $record, int|string|null $counterpartId): void
    {
        $current = $record->other_lang_id === null ? null : (int) $record->other_lang_id;
        $next = $counterpartId === null ? null : (int) $counterpartId;

        if ($current === $next) {
            return;
        }

        $record->other_lang_id = $next;
        $record->save();
    }
}
