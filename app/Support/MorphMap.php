<?php

namespace App\Support;

use App\Enums\ModelEnum;
use App\Models;
use App\Providers\AppServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Aliases stored in polymorphic `*_type` columns, registered in {@see AppServiceProvider::boot()}.
 *
 * Spelling is `Str::snake(class_basename())`, matching {@see ModelEnum} and the frontend.
 * Never re-spell an existing alias without a data migration — the alias *is* the stored value.
 * `requireMorphMap()` is deliberately off. Merging a branch cut before the map, and the rest of
 * the rules: `.ai/rules/support.md`.
 */
final class MorphMap
{
    /**
     * @var array<string, class-string<Model>>
     */
    public const MAP = [
        'activity' => Models\Activity::class,
        'agenda_item' => Models\Pivots\AgendaItem::class,
        'agenda_item_note' => Models\AgendaItemNote::class,
        'approval' => Models\Approval::class,
        'approval_flow' => Models\ApprovalFlow::class,
        'banner' => Models\Banner::class,
        'calendar' => Models\Calendar::class,
        'category' => Models\Category::class,
        'comment' => Models\Comment::class,
        'comment_poll_vote' => Models\CommentPollVote::class,
        'comment_reaction' => Models\CommentReaction::class,
        'content' => Models\Content::class,
        'content_part' => Models\ContentPart::class,
        'document' => Models\Document::class,
        'dutiable' => Models\Pivots\Dutiable::class,
        'duty' => Models\Duty::class,
        'field_response' => Models\FieldResponse::class,
        'file' => Models\File::class,
        'fileable_file' => Models\FileableFile::class,
        'form' => Models\Form::class,
        'form_field' => Models\FormField::class,
        'institution' => Models\Institution::class,
        'institution_check_in' => Models\InstitutionCheckIn::class,
        'institution_follow' => Models\InstitutionFollow::class,
        'institution_notification_mute' => Models\InstitutionNotificationMute::class,
        'lecturer_review' => Models\LecturerReview::class,
        'meeting' => Models\Meeting::class,
        'navigation' => Models\Navigation::class,
        'news' => Models\News::class,
        'notification_digest_queue' => Models\NotificationDigestQueue::class,
        'page' => Models\Page::class,
        'permission' => Models\Permission::class,
        'problem' => Models\Problem::class,
        'problem_category' => Models\ProblemCategory::class,
        'quick_link' => Models\QuickLink::class,
        'registration' => Models\Registration::class,
        'relationship' => Models\Relationship::class,
        'relationshipable' => Models\Pivots\Relationshipable::class,
        'reservation' => Models\Reservation::class,
        'reservation_resource' => Models\Pivots\ReservationResource::class,
        'resource' => Models\Resource::class,
        'resource_category' => Models\ResourceCategory::class,
        'role' => Models\Role::class,
        'role_type' => Models\RoleType::class,
        'sharepoint_file' => Models\SharepointFile::class,
        'sharepoint_fileable' => Models\Pivots\SharepointFileable::class,
        'study_program' => Models\StudyProgram::class,
        'study_set' => Models\StudySet::class,
        'study_set_course' => Models\StudySetCourse::class,
        'tag' => Models\Tag::class,
        'task' => Models\Task::class,
        'tenant' => Models\Tenant::class,
        'text_box_submission' => Models\TextBoxSubmission::class,
        'type' => Models\Type::class,
        'typeable' => Models\Typeable::class,
        'user' => Models\User::class,
        'vote' => Models\Vote::class,
    ];

    /**
     * Public search-index mirrors, which must resolve back to the record admins edit. A map is
     * keyed by alias and cannot hold two classes, so these override `getMorphClass()` instead.
     *
     * @var array<class-string, class-string>
     */
    public const ALIASED_TO_PARENT = [
        Models\PublicInstitution::class => Models\Institution::class,
        Models\PublicNews::class => Models\News::class,
        Models\PublicPage::class => Models\Page::class,
        Models\PublicMeeting::class => Models\Meeting::class,
    ];

    /**
     * The alias a model is stored under — needed whenever a `*_type` column is compared or
     * written directly. Relationship queries (`whereHasMorph`, `morphTo`) need no change.
     *
     * @param  class-string  $class
     */
    public static function alias(string $class): string
    {
        return Relation::getMorphAlias($class);
    }

    /**
     * The class an alias resolves to, or null — `survey` has no class yet, and historic rows
     * can outlive theirs.
     *
     * @return class-string|null
     */
    public static function classFor(string $alias): ?string
    {
        $class = Relation::getMorphedModel($alias);

        return is_string($class) && class_exists($class) ? $class : null;
    }
}
