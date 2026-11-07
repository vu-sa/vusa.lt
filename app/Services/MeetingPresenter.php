<?php

namespace App\Services;

use App\Enums\AgendaItemType;
use App\Enums\CalendarHeroStyleEnum;
use App\Enums\CommentKind;
use App\Enums\MeetingKind;
use App\Enums\MeetingType;
use App\Enums\TenantType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ResourceServices\SharepointFileService;
use App\Support\MeetingTitle;
use Closure;

class MeetingPresenter
{
    /** @return array{id: string, title: string, meeting_kind: MeetingKind|null, type: MeetingType|null, start_time: string, end_time: string|null, created_at: string, updated_at: string, deleted_at: string|null, description: array{lt: string, en: string}, title_noun: string, inherited_meeting_kind: MeetingKind, agenda_items: list<array{id: string, order: int, is_private: true, is_redacted: true, title: string, can: array{update: bool, delete: bool}}|array{id: string, meeting_id: string, created_at: string, updated_at: string, order: int, brought_by_students: bool, type: AgendaItemType|null, start_time: string|null, end_time: string|null, title: string|null, description: string|null, student_position: string|null, is_private: bool, public_title: string|null, votes: list<array{id: string, agenda_item_id: string, is_main: bool, is_consensus: bool, student_vote: string|null, decision: string|null, student_benefit: string|null, order: int, created_at: string|null, updated_at: string|null, title: string|null, note: string|null}>, comments_count: int, has_notes: bool, tenants: list<array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}>|null, meeting: array<string, mixed>|null, can: array{update: bool, delete: bool}}>, sharepointPath: string|null, sharepointFolderUrl: string|null, institutions: list<array{id: string, name: string|null, short_name: string|null, alias: string, description: string|null, address: string|null, phone: string|null, email: string|null, working_hours: string|null, website: string|null, logo_url: string|null, facebook_url: string|null, instagram_url: string|null, tenant_id: int|null, is_active: int, meeting_periodicity_days: int|null, created_at: string, updated_at: string, deleted_at: string|null, types: list<array{id: int, parent_id: int|null, title: string|null, description: string|null, slug: string|null, created_at: string|null, updated_at: string|null, deleted_at: string|null, pivot: array{institution_id: string, institution_type_id: int}, extra_attributes: array<array-key, mixed>|null}>, tenant: array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}|null, pivot: array{meeting_id: string, institution_id: string}}>, calendar_event: array{id: int, title: string|null, permalink: string|null, description: string|null, location: string|null, is_remote: bool, organizer: string|null, cto_url: string|null, facebook_url: string|null, main_image_focal_point: string|null, is_draft: bool, is_all_day: bool, is_international: bool, hero_style: CalendarHeroStyleEnum, date: string, end_date: string|null, event_type_id: int|null, tenant_id: int, meeting_id: string|null, created_at: string, updated_at: string, registration_form_id: int|null, deleted_at: string|null, main_image_url: string|null, main_image_media: array{id: int|null, url: string, thumb: string, srcset: string|null, width: int|null, height: int|null, focal_point: string|null, alt: string|null, author: string|null}|null, media: list<array{id: int, model_type: string, model_id: string, uuid: string|null, collection_name: string, name: string, file_name: string, mime_type: string|null, disk: string, conversions_disk: string|null, size: int, manipulations: array<array-key, mixed>, custom_properties: array<array-key, mixed>, generated_conversions: array<string, bool>, responsive_images: array<array-key, mixed>, order_column: int|null, created_at: string|null, updated_at: string|null, original_url: string, preview_url: string}>}|null, is_public: bool, is_joint: bool, has_protocol: bool, has_report: bool, tenants: list<array<string, mixed>>|null}|array{id: string, title: string, meeting_kind: MeetingKind|null, type: MeetingType|null, start_time: string, end_time: string|null, created_at: string, updated_at: string, deleted_at: string|null, description: array{lt: string, en: string}, title_noun: string, inherited_meeting_kind: MeetingKind, agenda_items: list<array{id: string, order: int, is_private: true, is_redacted: true, title: string, can: array{update: bool, delete: bool}}|array{id: string, meeting_id: string, created_at: string, updated_at: string, order: int, brought_by_students: bool, type: AgendaItemType|null, start_time: string|null, end_time: string|null, title: string|null, description: string|null, student_position: string|null, is_private: bool, public_title: string|null, votes: list<array{id: string, agenda_item_id: string, is_main: bool, is_consensus: bool, student_vote: string|null, decision: string|null, student_benefit: string|null, order: int, created_at: string|null, updated_at: string|null, title: string|null, note: string|null}>, comments_count: int, has_notes: bool, tenants: list<array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}>|null, meeting: array<string, mixed>|null, can: array{update: bool, delete: bool}}>, sharepointPath: string|null, sharepointFolderUrl: string|null, institutions: list<array{id: string, name: string|null, short_name: string|null, alias: string, description: string|null, address: string|null, phone: string|null, email: string|null, working_hours: string|null, website: string|null, logo_url: string|null, facebook_url: string|null, instagram_url: string|null, tenant_id: int|null, is_active: int, meeting_periodicity_days: int|null, created_at: string, updated_at: string, deleted_at: string|null, types: list<array{id: int, parent_id: int|null, title: string|null, description: string|null, slug: string|null, created_at: string|null, updated_at: string|null, deleted_at: string|null, pivot: array{institution_id: string, institution_type_id: int}, extra_attributes: array<array-key, mixed>|null}>, tenant: array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}|null, pivot: array{meeting_id: string, institution_id: string}}>, calendar_event: array{id: int, title: string|null, permalink: string|null, description: string|null, location: string|null, is_remote: bool, organizer: string|null, cto_url: string|null, facebook_url: string|null, main_image_focal_point: string|null, is_draft: bool, is_all_day: bool, is_international: bool, hero_style: CalendarHeroStyleEnum, date: string, end_date: string|null, event_type_id: int|null, tenant_id: int, meeting_id: string|null, created_at: string, updated_at: string, registration_form_id: int|null, deleted_at: string|null, main_image_url: string|null, main_image_media: array{id: int|null, url: string, thumb: string, srcset: string|null, width: int|null, height: int|null, focal_point: string|null, alt: string|null, author: string|null}|null, media: list<array{id: int, model_type: string, model_id: string, uuid: string|null, collection_name: string, name: string, file_name: string, mime_type: string|null, disk: string, conversions_disk: string|null, size: int, manipulations: array<array-key, mixed>, custom_properties: array<array-key, mixed>, generated_conversions: array<string, bool>, responsive_images: array<array-key, mixed>, order_column: int|null, created_at: string|null, updated_at: string|null, original_url: string, preview_url: string}>}|null, is_public: bool, is_joint: bool, has_protocol: bool, has_report: bool, comments_count: int, tasks_count: int, documents_count: int, fileable_files: list<array{id: string, fileable_type: string, fileable_id: string, sharepoint_id: string, sharepoint_path: string|null, name: string, file_type: string|null, mime_type: string|null, size_bytes: int|null, file_date: string|null, description: string|null, public_link: string|null, public_link_expires_at: string|null, last_synced_at: string|null, deleted_externally_at: string|null, created_at: string|null, updated_at: string|null, formatted_size: string|null}>, comments: list<array{id: string, parent_id: string|null, thread_root_id: string|null, commentable_type: string, commentable_id: string, user_id: string|null, kind: CommentKind, body: string, resolved_at: string|null, resolved_by: string|null, edited_at: string|null, erased_at: string|null, created_at: string|null, updated_at: string|null, user: array{id: string, name: string, profile_photo_path: string|null}|null, metadata: array<array-key, mixed>|null, mentioned_user_ids: list<string>|null}>, tenants: list<array<string, mixed>>|null} */
    public static function forShow(Meeting $meeting, User $user, bool $readOnly, Closure $agendaItemAbilities): array
    {
        $attributes = $meeting->attributesToArray();
        $tenants = $meeting->relationLoaded('tenants') ? $meeting->tenants->toArray() : null;
        $agendaItems = [];
        foreach ($meeting->agendaItems as $item) {
            $agendaItems[] = self::agendaItem($item, $user, $agendaItemAbilities);
        }

        $institutions = [];
        foreach ($meeting->institutions as $institution) {
            $institutionPayload = $institution->toArray();
            if ($institution->tenant !== null) {
                $institutionPayload['tenant'] = self::tenant($institution->tenant);
            }
            $institutions[] = $institutionPayload;
        }

        $calendarEvent = $meeting->calendarEvent?->toArray();
        if ($calendarEvent !== null) {
            $calendarEvent['hero_style'] = $meeting->calendarEvent->hero_style;
        }

        $description = $meeting->getTranslations('description');
        $payload = [
            'id' => $attributes['id'],
            'title' => $attributes['title'],
            'meeting_kind' => $meeting->meeting_kind,
            'type' => $meeting->type,
            'start_time' => $attributes['start_time'],
            'end_time' => $attributes['end_time'],
            'created_at' => $attributes['created_at'],
            'updated_at' => $attributes['updated_at'],
            'deleted_at' => $attributes['deleted_at'],
            'description' => ['lt' => $description['lt'] ?? '', 'en' => $description['en'] ?? ''],
            'title_noun' => MeetingTitle::noun($meeting->type, app()->getLocale(), $meeting->meetingKind(), joint: $meeting->is_joint),
            'inherited_meeting_kind' => $meeting->institutions->first()?->resolvedMeetingKind() ?? MeetingKind::Sitting,
            'agenda_items' => $agendaItems,
            'institutions' => $institutions,
            'calendar_event' => $calendarEvent,
            'is_public' => (bool) $meeting->is_public,
            'is_joint' => (bool) $meeting->is_joint,
            'has_protocol' => $meeting->has_protocol,
            'has_report' => $meeting->has_report,
            'sharepointPath' => $readOnly ? null : SharepointFileService::pathOrNull($meeting),
            'sharepointFolderUrl' => $readOnly ? null : SharepointFileService::folderUrlOrNull($meeting),
        ];

        $payload['tenants'] = $tenants;

        if (! $readOnly) {
            $payload['comments_count'] = (int) $meeting->getAttribute('comments_count');
            $payload['tasks_count'] = (int) $meeting->getAttribute('tasks_count');
            $payload['documents_count'] = (int) $meeting->getAttribute('documents_count');
            $payload['fileable_files'] = $meeting->fileableFiles->toArray();
            $comments = [];
            foreach ($meeting->comments as $comment) {
                $commentPayload = $comment->toArray();
                $commentPayload['kind'] = $comment->kind;
                $comments[] = $commentPayload;
            }
            $payload['comments'] = $comments;
        }

        return $payload;
    }

    /** @return array{id: string, order: int, is_private: true, is_redacted: true, title: string, can: array{update: bool, delete: bool}}|array{id: string, meeting_id: string, created_at: string, updated_at: string, order: int, brought_by_students: bool, type: AgendaItemType|null, start_time: string|null, end_time: string|null, title: string|null, description: string|null, student_position: string|null, is_private: bool, public_title: string|null, votes: list<array{id: string, agenda_item_id: string, is_main: bool, is_consensus: bool, student_vote: string|null, decision: string|null, student_benefit: string|null, order: int, created_at: string|null, updated_at: string|null, title: string|null, note: string|null}>, comments_count: int, has_notes: bool, tenants: list<array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}>|null, meeting: array<string, mixed>|null, can: array{update: bool, delete: bool}} */
    private static function agendaItem(AgendaItem $item, User $user, Closure $abilities): array
    {
        $payload = AgendaItemPresenter::forUser($item, $user);
        $payload['can'] = $abilities()->get((string) $item->getKey());

        return $payload;
    }

    /** @return array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null} */
    public static function tenant(Tenant $tenant): array
    {
        $attributes = $tenant->attributesToArray();

        return [
            'id' => $attributes['id'],
            'type' => $tenant->type,
            'fullname' => $attributes['fullname'],
            'shortname' => $attributes['shortname'],
            'alias' => $attributes['alias'],
            'phone' => $attributes['phone'],
            'email' => $attributes['email'],
            'address' => $attributes['address'],
            'shortname_vu' => $attributes['shortname_vu'],
            'primary_institution_id' => $attributes['primary_institution_id'],
            'goals_enabled' => $attributes['goals_enabled'],
            'created_at' => $attributes['created_at'],
            'updated_at' => $attributes['updated_at'],
        ];
    }

    /** @return array{update: bool, delete: bool, discard: bool, createAgendaItems: bool, reorderAgendaItems: bool, attachInstitution: bool} */
    public static function abilities(bool $update, bool $delete, bool $discard, bool $createAgendaItems): array
    {
        return [
            'update' => $update,
            'delete' => $delete,
            'discard' => $discard,
            'createAgendaItems' => $createAgendaItems,
            'reorderAgendaItems' => $update,
            'attachInstitution' => $update,
        ];
    }

    /** @return array{mode: 'public_page'|'calendar_event'|'calendar_draft'|'internal', url: string|null} */
    public static function publicVisibility(Meeting $meeting, string $locale): array
    {
        return $meeting->publicVisibility($locale);
    }

    /** @return array{status: 'complete'|'incomplete'|'no_items'|null, missingActions: list<array{type: 'agenda_missing'}|array{type: 'agenda_item_type_missing', agenda_item_id: string, title: string, position: int}|array{type: 'agenda_item_vote_missing', agenda_item_id: string, title: string, position: int, missing_fields: list<'decision'|'student_vote'|'student_benefit'>}>} */
    public static function completion(?string $status, array $missingActions): array
    {
        return ['status' => $status, 'missingActions' => $missingActions];
    }
}
