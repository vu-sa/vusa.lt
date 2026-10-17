<?php

namespace App\Actions;

use App\Listeners\HandleDutiableChange;
use App\Models\Comment;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Merges user accounts into one, repointing every row that references a merged
 * account onto the kept one and then deleting the merged accounts permanently.
 *
 * Every column referencing users belongs to exactly one of the maps below;
 * UserMergeCoverageTest fails when a new one is added without a decision.
 * Eligibility (matching names, no direct roles) is checked by MergeUsersRequest.
 */
class MergeUsers
{
    /** Columns repointed as they are. */
    public const array REPOINT = [
        ['agenda_item_notes', 'updated_by'],
        ['approvals', 'reverted_by_id'],
        ['approvals', 'user_id'],
        ['comments', 'resolved_by'],
        ['comments', 'user_id'],
        ['documents', 'published_by'],
        ['goals', 'created_by'],
        ['institution_activity_requests', 'recipient_id'],
        ['institution_activity_requests', 'requested_by_id'],
        ['institution_check_ins', 'user_id'],
        ['notification_digest_queue', 'user_id'],
        ['problems', 'created_by'],
        ['problems', 'responsible_user_id'],
        ['registrations', 'user_id'],
        ['steps', 'created_by'],
        ['support_requests', 'assigned_to'],
        ['support_requests', 'created_by'],
        ['text_box_submissions', 'user_id'],
    ];

    /**
     * user_id columns repointed only where the kept user has no row sharing these
     * columns; the merged account's duplicate is dropped instead.
     */
    public const array REPOINT_DEDUPED = [
        'comment_poll_votes' => ['comment_id'],
        'comment_reactions' => ['comment_id', 'emoji'],
        'content_editor_drafts' => ['kind', 'identity'],
        'institution_follows' => ['institution_id'],
        'institution_secretaries' => ['institution_id', 'cadence_id'],
        'reservation_drafts' => [],
        'reservation_user' => ['reservation_id'],
        'step_user' => ['step_id'],
        'support_request_user' => ['support_request_id'],
        'task_user' => ['task_id'],
    ];

    /** Polymorphic owners: table => [morph name, columns shared with an existing kept row]. */
    public const array REPOINT_MORPH = [
        'activity_log' => ['causer', null],
        'notifications' => ['notifiable', null],
        'push_subscriptions' => ['subscribable', ['endpoint']],
    ];

    /** user_id rows that mean nothing once the account is gone. */
    public const array DROP = ['sessions'];

    /** Profile fields the kept user takes over from a merged account when it has none. */
    private const array FILLABLE_WHEN_BLANK = ['phone', 'facebook_url', 'profile_photo_path', 'profile_photo_focal_point', 'pronouns'];

    /**
     * @param  User  $keptUser  The user account to keep
     * @param  Collection<int, User>  $sources  User accounts to merge and delete
     * @param  string  $loginEmail  The address the kept account signs in with from now on
     */
    public static function execute(User $keptUser, Collection $sources, string $loginEmail, ?User $actor = null): void
    {
        DB::transaction(function () use ($keptUser, $sources, $loginEmail, $actor): void {
            $affectedDutyIds = collect();
            $merged = [];

            foreach ($sources as $source) {
                $affectedDutyIds = $affectedDutyIds->merge(self::moveDutiables($keptUser, $source));
                self::moveRows($keptUser, $source);
                self::fillBlankAttributes($keptUser, $source);

                $merged[] = ['id' => $source->id, 'name' => $source->name, 'email' => $source->email];
                $source->forceDelete();
            }

            foreach ($affectedDutyIds->unique() as $dutyId) {
                $duty = Duty::find($dutyId);

                if ($duty) {
                    CollapseOverlappingDutiables::execute($duty);
                }
            }

            // Only after the merged accounts are gone does their address free up in the unique index.
            $keptUser->email = $loginEmail;
            $keptUser->save();

            activity()
                ->performedOn($keptUser)
                ->causedBy($actor)
                ->event('users_merged')
                ->withProperties(['merged' => $merged])
                ->log('users_merged');

            // Dutiables move by query, so no DutiableChanged fires for the kept user.
            DB::afterCommit(function () use ($keptUser): void {
                HandleDutiableChange::forgetUserAuthority((string) $keptUser->id);
                $keptUser->fresh()?->searchable();
            });
        });
    }

    /**
     * Repointed in bulk (not pivot-by-pivot) so a duty both users hold lands on the
     * kept user exactly once; CollapseOverlappingDutiables then folds any overlap.
     *
     * @return Collection<int, string>
     */
    private static function moveDutiables(User $keptUser, User $mergedUser): Collection
    {
        $query = fn () => Dutiable::query()
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->where('dutiable_id', $mergedUser->id);

        $affectedDutyIds = $query()->pluck('duty_id')->unique();
        $query()->update(['dutiable_id' => $keptUser->id]);

        return $affectedDutyIds;
    }

    private static function moveRows(User $keptUser, User $mergedUser): void
    {
        foreach (self::REPOINT as [$table, $column]) {
            DB::table($table)->where($column, $mergedUser->id)->update([$column => $keptUser->id]);
        }

        foreach (self::REPOINT_DEDUPED as $table => $sharedColumns) {
            self::repointDeduped($table, 'user_id', $sharedColumns, $keptUser, $mergedUser);
        }

        $userAlias = MorphMap::alias(User::class);

        foreach (self::REPOINT_MORPH as $table => [$morph, $sharedColumns]) {
            if ($sharedColumns !== null) {
                self::repointDeduped($table, "{$morph}_id", $sharedColumns, $keptUser, $mergedUser, ["{$morph}_type" => $userAlias]);

                continue;
            }

            DB::table($table)
                ->where("{$morph}_type", $userAlias)
                ->where("{$morph}_id", $mergedUser->id)
                ->update(["{$morph}_id" => $keptUser->id]);
        }

        foreach (self::DROP as $table) {
            DB::table($table)->where('user_id', $mergedUser->id)->delete();
        }

        Comment::query()
            ->whereJsonContains('mentioned_user_ids', $mergedUser->id)
            ->each(function (Comment $comment) use ($keptUser, $mergedUser): void {
                $comment->mentioned_user_ids = collect($comment->mentioned_user_ids)
                    ->map(fn (mixed $id) => $id === $mergedUser->id ? $keptUser->id : $id)
                    ->unique()
                    ->values()
                    ->all();
                $comment->saveQuietly();
            });
    }

    /**
     * Drop the merged user's rows the kept user already has a twin of (same values in
     * $sharedColumns), then move the rest — a unique index would reject the update.
     *
     * @param  list<string>  $sharedColumns
     * @param  array<string, string>  $scope  Extra equality constraints, e.g. the morph type
     */
    private static function repointDeduped(string $table, string $ownerColumn, array $sharedColumns, User $keptUser, User $mergedUser, array $scope = []): void
    {
        DB::table($table)->where($scope)->where($ownerColumn, $mergedUser->id)
            ->whereExists(function (Builder $twin) use ($table, $ownerColumn, $sharedColumns, $scope, $keptUser): void {
                $twin->select(DB::raw(1))
                    ->from("{$table} as twin")
                    ->where("twin.{$ownerColumn}", $keptUser->id);

                foreach ($scope as $column => $value) {
                    $twin->where("twin.{$column}", $value);
                }

                foreach ($sharedColumns as $column) {
                    $twin->whereColumn("twin.{$column}", "{$table}.{$column}");
                }
            })
            ->delete();

        DB::table($table)->where($scope)->where($ownerColumn, $mergedUser->id)->update([$ownerColumn => $keptUser->id]);
    }

    private static function fillBlankAttributes(User $keptUser, User $mergedUser): void
    {
        foreach (self::FILLABLE_WHEN_BLANK as $attribute) {
            if ($keptUser->isTranslatableAttribute($attribute)) {
                if (array_filter($keptUser->getTranslations($attribute)) === [] && array_filter($mergedUser->getTranslations($attribute)) !== []) {
                    $keptUser->setTranslations($attribute, $mergedUser->getTranslations($attribute));
                }

                continue;
            }

            if (blank($keptUser->getAttribute($attribute)) && filled($mergedUser->getAttribute($attribute))) {
                $keptUser->setAttribute($attribute, $mergedUser->getAttribute($attribute));
            }
        }
    }
}
