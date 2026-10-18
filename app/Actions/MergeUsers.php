<?php

namespace App\Actions;

use App\Listeners\HandleDutiableChange;
use App\Models\Comment;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Services\ContactSearchIndexSynchronizer;
use App\Services\Media\LegacyImageImporter;
use App\Services\UserMergeDutyEmails;
use App\Support\MergeRepointer;
use App\Support\MorphMap;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Transfers related data before permanently deleting duplicate accounts; UserMergeCoverageTest keeps the maps complete. */
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
        ['mailing_list_exclusions', 'created_by'],
        ['mailing_list_members', 'user_id'],
        ['mailing_list_sources', 'created_by'],
        ['notification_digest_queue', 'user_id'],
        ['pending_uploads', 'user_id'],
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
    private const array FILLABLE_WHEN_BLANK = ['phone', 'facebook_url', 'pronouns'];

    /** @param Collection<int, User> $sources */
    public static function execute(User $keptUser, Collection $sources, string $loginEmail, ?User $actor = null, array $dutiableEmailIds = []): void
    {
        DB::transaction(function () use ($keptUser, $sources, $loginEmail, $actor, $dutiableEmailIds): void {
            $affectedDutyIds = collect();
            $merged = [];
            $preservedEmails = [];

            if ($dutiableEmailIds !== []) {
                abort_unless($actor instanceof User, 403);
                $preservedEmails = app(UserMergeDutyEmails::class)->preserve($keptUser, $sources, $loginEmail, $actor, $dutiableEmailIds);
            }

            self::copyDutyPhotos($keptUser, $sources);

            foreach ($sources as $source) {
                $affectedDutyIds = $affectedDutyIds->merge(self::moveDutiables($keptUser, $source));
                self::moveRows($keptUser, $source);
                self::fillBlankAttributes($keptUser, $source);
                self::mergeProfilePhoto($keptUser, $source);

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
                ->withProperties(['merged' => $merged, 'preserved_dutiable_emails' => $preservedEmails])
                ->log('users_merged');

            // Dutiables move by query, so no DutiableChanged fires for the kept user.
            DB::afterCommit(function () use ($keptUser): void {
                HandleDutiableChange::forgetUserAuthority((string) $keptUser->id);
                $fresh = $keptUser->fresh();
                $fresh?->searchable();
                if ($fresh !== null) {
                    app(ContactSearchIndexSynchronizer::class)->userChanged($fresh);
                }
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
            MergeRepointer::plain($table, $column, $keptUser->id, $mergedUser->id);
        }

        foreach (self::REPOINT_DEDUPED as $table => $sharedColumns) {
            MergeRepointer::deduped($table, 'user_id', $sharedColumns, $keptUser->id, $mergedUser->id);
        }

        foreach (self::REPOINT_MORPH as $table => [$morph, $sharedColumns]) {
            MergeRepointer::morph($table, $morph, $sharedColumns, MorphMap::alias(User::class), $keptUser->id, $mergedUser->id);
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

    private static function mergeProfilePhoto(User $keptUser, User $source): void
    {
        if ($keptUser->getFirstMedia('profile_photo') !== null || filled($keptUser->profile_photo_path)) {
            return;
        }

        $photo = $source->getFirstMedia('profile_photo');

        if ($photo === null) {
            $keptUser->profile_photo_path = $source->profile_photo_path;
            $keptUser->profile_photo_focal_point = $source->profile_photo_focal_point;

            return;
        }

        // Files are keyed by media id; reassigning ownership keeps their URLs and conversions.
        $photo->model()->associate($keptUser);
        $photo->save();
        $source->unsetRelation('media');
        $keptUser->refreshImageCache('profile_photo');
        DB::afterCommit(fn () => $keptUser->fresh()?->afterImageMediaChanged('profile_photo'));
    }

    /** @param Collection<int, User> $sources */
    private static function copyDutyPhotos(User $kept, Collection $sources): void
    {
        $rows = Dutiable::query()->where('dutiable_type', MorphMap::alias(User::class))
            ->whereIn('dutiable_id', [$kept->id, ...$sources->pluck('id')->all()])->with('media')->get();
        $eligible = collect();
        foreach ($rows->groupBy(fn (Dutiable $row) => $row->duty_id.'|'.$row->tenant_id) as $group) {
            foreach (CollapseOverlappingDutiables::overlappingGroups($group) as $overlap) {
                if (! $overlap->contains(fn (Dutiable $row) => filled($row->additional_photo) || $row->getFirstMedia('photo') !== null)) {
                    $eligible = $eligible->merge($overlap);
                }
            }
        }

        foreach ($sources as $source) {
            self::copyPhotoToPeriods($source, $eligible->where('dutiable_id', $source->id));
        }
    }

    /** @param Collection<int, Dutiable> $periods */
    private static function copyPhotoToPeriods(User $source, Collection $periods): void
    {
        if ($periods->isEmpty()) {
            return;
        }

        $photo = $source->getFirstMedia('profile_photo')
            ?? app(LegacyImageImporter::class)->importCollection($source, 'profile_photo');

        foreach ($periods as $period) {
            if ($photo !== null) {
                $photo->copy($period, 'photo');
                $period->refreshImageCache('photo');
                DB::afterCommit(fn () => $period->fresh()?->afterImageMediaChanged('photo'));
            } elseif (filled($source->profile_photo_path)) {
                $period->update([
                    'additional_photo' => $source->profile_photo_path,
                    'additional_photo_focal_point' => $source->profile_photo_focal_point,
                ]);
            }
        }
    }
}
