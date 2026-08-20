<?php

namespace App\Actions;

use App\Models\Comment;
use App\Models\Duty;
use App\Models\InstitutionCheckIn;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Support\Facades\DB;

/**
 * Merges one user account into another, repointing every relationship from the
 * merged user onto the kept user.
 *
 * Sibling to {@see MergeDuties}. Dutiable rows are repointed in bulk (not
 * pivot-by-pivot) so a duty both users hold lands on the kept user exactly
 * once — {@see CollapseOverlappingDutiables} then folds any overlapping rows
 * this produces. The many-to-many pivots (tasks, reservations) are repointed at
 * the pivot table with the kept user's existing
 * rows de-duplicated, and HasMany records (comments, check-ins) are repointed
 * directly. The merged user is soft-deleted, like duties are on merge, so a
 * botched merge is recoverable.
 */
class MergeUsers
{
    /**
     * @param  User  $keptUser  The user account to keep
     * @param  User  $mergedUser  The user account to merge and delete
     */
    public static function execute(User $keptUser, User $mergedUser): void
    {
        DB::transaction(function () use ($keptUser, $mergedUser): void {
            // Capture the merged user's duties before repointing, so each can be
            // collapsed afterwards for overlaps with the kept user's own rows.
            $affectedDutyIds = Dutiable::query()
                ->where('dutiable_type', MorphMap::alias(User::class))
                ->where('dutiable_id', $mergedUser->id)
                ->pluck('duty_id')
                ->unique();

            // Repoint every dutiable row the merged user holds onto the kept user.
            Dutiable::query()
                ->where('dutiable_type', MorphMap::alias(User::class))
                ->where('dutiable_id', $mergedUser->id)
                ->update(['dutiable_id' => $keptUser->id]);

            // A duty both users held now carries two rows for the kept user — fold
            // any that overlap into one (per tenant scope), keeping separate stints.
            foreach ($affectedDutyIds as $dutyId) {
                $duty = Duty::find($dutyId);

                if ($duty) {
                    CollapseOverlappingDutiables::execute($duty);
                }
            }

            // Many-to-many pivots: drop rows that would duplicate one the kept user
            // already has, then repoint the rest. (table, related-key column)
            self::repointPivot('task_user', 'task_id', $keptUser, $mergedUser);
            self::repointPivot('reservation_user', 'reservation_id', $keptUser, $mergedUser);

            // HasMany records — repoint the foreign key directly.
            Comment::query()->where('user_id', $mergedUser->id)->update(['user_id' => $keptUser->id]);
            InstitutionCheckIn::query()->where('user_id', $mergedUser->id)->update(['user_id' => $keptUser->id]);

            // Finally, soft-delete the merged user (recoverable, like MergeDuties).
            $mergedUser->delete();
        });
    }

    /**
     * Repoint a (related, user_id) pivot from the merged user onto the kept
     * user, first dropping any rows that would duplicate a pairing the kept
     * user already holds (the pivot has no uniqueness constraint to lean on).
     */
    private static function repointPivot(string $table, string $relatedKey, User $keptUser, User $mergedUser): void
    {
        DB::table($table)->where('user_id', $mergedUser->id)
            ->whereIn($relatedKey, fn ($q) => $q->select($relatedKey)
                ->from($table)
                ->where('user_id', $keptUser->id))
            ->delete();

        DB::table($table)->where('user_id', $mergedUser->id)->update(['user_id' => $keptUser->id]);
    }
}
