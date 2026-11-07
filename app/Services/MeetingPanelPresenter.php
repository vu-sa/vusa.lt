<?php

namespace App\Services;

use App\Actions\Documents\PendingMeetingDocuments;
use App\Actions\GetAttachableMeetingInstitutions;
use App\Enums\DocumentStatus;
use App\Enums\TenantType;
use App\Http\Resources\DocumentRowResource;
use App\Http\Resources\TaskResource;
use App\Models\Document;
use App\Models\Duty;
use App\Models\Meeting;
use App\Models\User;
use App\Tasks\Enums\ActionType;
use Illuminate\Support\Collection;

class MeetingPanelPresenter
{
    /** @return list<array{id: string, fileable_type: string, fileable_id: string, sharepoint_id: string, sharepoint_path: string|null, name: string, file_type: string|null, mime_type: string|null, size_bytes: int|null, file_date: string|null, description: string|null, public_link: string|null, public_link_expires_at: string|null, last_synced_at: string|null, deleted_externally_at: string|null, created_at: string|null, updated_at: string|null, formatted_size: string|null}> */
    public static function files(Meeting $meeting, bool $readOnly): array
    {
        $files = [];
        if (! $readOnly) {
            foreach ($meeting->fileableFiles->whereNull('deleted_externally_at')->sortByDesc('file_date')->values() as $file) {
                $files[] = FileableFilePresenter::file($file);
            }
        }

        return $files;
    }

    /** @param Collection<int, User> $users
     * @return list<array{id: string, email: string|null, phone: string|null, facebook_url: string|null, name: string, pronouns: string|null, show_pronouns: bool, is_active: int, updated_at: string, created_at: string, profile_photo_path: string|null, profile_photo_focal_point: string|null, deleted_at: string|null, duties: list<array{id: string, name: string|null, description: string|null, institution_id: string, order: int, email: string|null, contacts_grouping: string, places_to_occupy: int|null, is_active: bool, created_at: string, updated_at: string, deleted_at: string|null, pivot: array{dutiable_type: string, dutiable_id: string, duty_id: string, id: string, via_dutiable_id: string|null, tenant_id: int|null, start_date: string, end_date: string|null, additional_photo: string|null, additional_photo_focal_point: string|null, additional_email: string|null, use_original_duty_name: bool, description: string|null, study_program_id: string|null}}>}> */
    public static function representatives(Collection $users): array
    {
        $representatives = [];
        foreach ($users as $user) {
            $representatives[] = self::representative($user);
        }

        return $representatives;
    }

    /** @return array{id: string, email: string|null, phone: string|null, facebook_url: string|null, name: string, pronouns: string|null, show_pronouns: bool, is_active: int, updated_at: string, created_at: string, profile_photo_path: string|null, profile_photo_focal_point: string|null, deleted_at: string|null, duties: list<array{id: string, name: string|null, description: string|null, institution_id: string, order: int, email: string|null, contacts_grouping: string, places_to_occupy: int|null, is_active: bool, created_at: string, updated_at: string, deleted_at: string|null, pivot: array{dutiable_type: string, dutiable_id: string, duty_id: string, id: string, via_dutiable_id: string|null, tenant_id: int|null, start_date: string, end_date: string|null, additional_photo: string|null, additional_photo_focal_point: string|null, additional_email: string|null, use_original_duty_name: bool, description: string|null, study_program_id: string|null}}>} */
    private static function representative(User $user): array
    {
        $payload = UserPresenter::user($user);
        $duties = [];
        foreach ($user->duties as $duty) {
            $duties[] = self::representativeDuty($duty);
        }
        $payload['duties'] = $duties;

        return $payload;
    }

    /** @return array{id: string, name: string|null, description: string|null, institution_id: string, order: int, email: string|null, contacts_grouping: string, places_to_occupy: int|null, is_active: bool, created_at: string, updated_at: string, deleted_at: string|null, pivot: array{dutiable_type: string, dutiable_id: string, duty_id: string, id: string, via_dutiable_id: string|null, tenant_id: int|null, start_date: string, end_date: string|null, additional_photo: string|null, additional_photo_focal_point: string|null, additional_email: string|null, use_original_duty_name: bool, description: string|null, study_program_id: string|null}} */
    private static function representativeDuty(Duty $duty): array
    {
        $attributes = $duty->attributesToArray();

        return [
            'id' => $attributes['id'],
            'name' => $attributes['name'] === null ? null : (string) $duty->name,
            'description' => $attributes['description'] === null ? null : (string) $duty->description,
            'institution_id' => $attributes['institution_id'],
            'order' => $attributes['order'],
            'email' => $attributes['email'],
            'contacts_grouping' => $attributes['contacts_grouping'],
            'places_to_occupy' => $attributes['places_to_occupy'],
            'is_active' => $attributes['is_active'],
            'created_at' => $attributes['created_at'],
            'updated_at' => $attributes['updated_at'],
            'deleted_at' => $attributes['deleted_at'],
            'pivot' => $duty->pivot->toArray(),
        ];
    }

    /** @return list<array{id: string, name: string, description: string|null, due_date: string|null, completed_at: string|null, cancelled_at: string|null, created_at: string, updated_at: string, action_type: ActionType|null, metadata: array<string, mixed>|null, progress: array{current: int, total: int, percentage: int}|null, is_overdue: bool, can_be_manually_completed: bool, can_update: bool, icon: string, color: string, taskable: array{id: int|string, name: string|null, type: string}|null, taskable_type: string, taskable_id: string, users: list<array{id: string, name: string, profile_photo_path: string|null}>}> */
    public static function tasks(Meeting $meeting): array
    {
        $tasks = [];
        foreach ($meeting->tasks()->with('users:id,name,email,profile_photo_path', 'taskable', 'tenants')->get() as $task) {
            $tasks[] = new TaskResource($task)->resolve(request());
        }

        return $tasks;
    }

    /** @return list<array{id: int, name: string, title: string, document_date: string|null, institution_id: string|null, meeting_id: string|null, content_type: string|null, language: string|null, summary: string|null, anonymous_url: string|null, link_url: string|null, status: DocumentStatus, removed_from_sharepoint_at: string|null, published_at: string|null, sharepoint_modified_at: string|null, checked_at: string|null, sync_status: 'pending'|'syncing'|'success'|'failed'|'imported', sync_error_message: string|null, sync_attempts: int, last_sync_attempt_at: string|null, effective_date: string|null, expiration_date: string|null, language_code: string, institution: array{id: string, name: string|null, short_name: string|null, alias: string, description: string|null, address: string|null, phone: string|null, email: string|null, working_hours: string|null, website: string|null, logo_url: string|null, facebook_url: string|null, instagram_url: string|null, tenant_id: int|null, is_active: int, meeting_periodicity_days: int|null, created_at: string, updated_at: string, deleted_at: string|null, tenant: array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}|null}|null}|array{id: int, name: string, title: string, document_date: string|null, institution_id: string|null, meeting_id: string|null, content_type: string|null, language: string|null, summary: string|null, anonymous_url: string|null, link_url: string|null, status: DocumentStatus, removed_from_sharepoint_at: string|null, published_at: string|null, sharepoint_modified_at: string|null, checked_at: string|null, sync_status: 'pending'|'syncing'|'success'|'failed'|'imported', sync_error_message: string|null, sync_attempts: int, last_sync_attempt_at: string|null, effective_date: string|null, expiration_date: string|null, language_code: string, institution: array{id: string, name: string|null, short_name: string|null, alias: string, description: string|null, address: string|null, phone: string|null, email: string|null, working_hours: string|null, website: string|null, logo_url: string|null, facebook_url: string|null, instagram_url: string|null, tenant_id: int|null, is_active: int, meeting_periodicity_days: int|null, created_at: string, updated_at: string, deleted_at: string|null, tenant: array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}|null}|null, tenant: list<array<string, mixed>>}> */
    public static function documents(Meeting $meeting, User $user): array
    {
        $documents = [];
        $linked = $meeting->documents()->with('institution.tenant')->orderBy('document_date')->orderBy('title')->get();
        foreach ($linked as $document) {
            if ($document->isPublished() || $user->can('view', $document)) {
                $documents[] = self::document($document);
            }
        }

        return $documents;
    }

    /** @return array{id: int, name: string, title: string, document_date: string|null, institution_id: string|null, meeting_id: string|null, content_type: string|null, language: string|null, summary: string|null, anonymous_url: string|null, link_url: string|null, status: DocumentStatus, removed_from_sharepoint_at: string|null, published_at: string|null, sharepoint_modified_at: string|null, checked_at: string|null, sync_status: 'pending'|'syncing'|'success'|'failed'|'imported', sync_error_message: string|null, sync_attempts: int, last_sync_attempt_at: string|null, effective_date: string|null, expiration_date: string|null, language_code: string, institution: array{id: string, name: string|null, short_name: string|null, alias: string, description: string|null, address: string|null, phone: string|null, email: string|null, working_hours: string|null, website: string|null, logo_url: string|null, facebook_url: string|null, instagram_url: string|null, tenant_id: int|null, is_active: int, meeting_periodicity_days: int|null, created_at: string, updated_at: string, deleted_at: string|null, tenant: array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}|null}|null}|array{id: int, name: string, title: string, document_date: string|null, institution_id: string|null, meeting_id: string|null, content_type: string|null, language: string|null, summary: string|null, anonymous_url: string|null, link_url: string|null, status: DocumentStatus, removed_from_sharepoint_at: string|null, published_at: string|null, sharepoint_modified_at: string|null, checked_at: string|null, sync_status: 'pending'|'syncing'|'success'|'failed'|'imported', sync_error_message: string|null, sync_attempts: int, last_sync_attempt_at: string|null, effective_date: string|null, expiration_date: string|null, language_code: string, institution: array{id: string, name: string|null, short_name: string|null, alias: string, description: string|null, address: string|null, phone: string|null, email: string|null, working_hours: string|null, website: string|null, logo_url: string|null, facebook_url: string|null, instagram_url: string|null, tenant_id: int|null, is_active: int, meeting_periodicity_days: int|null, created_at: string, updated_at: string, deleted_at: string|null, tenant: array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}|null}|null, tenant: list<array<string, mixed>>} */
    private static function document(Document $document): array
    {
        $attributes = $document->attributesToArray();
        $institution = $document->institution?->toArray();
        if ($document->institution?->tenant !== null) {
            $institution['tenant'] = MeetingPresenter::tenant($document->institution->tenant);
        }
        $payload = [
            'id' => $attributes['id'],
            'name' => $attributes['name'],
            'title' => $attributes['title'],
            'document_date' => $attributes['document_date'],
            'institution_id' => $attributes['institution_id'],
            'meeting_id' => $attributes['meeting_id'],
            'content_type' => $attributes['content_type'],
            'language' => $attributes['language'],
            'summary' => $attributes['summary'],
            'anonymous_url' => $attributes['anonymous_url'],
            'link_url' => $attributes['link_url'],
            'status' => $document->status,
            'removed_from_sharepoint_at' => $attributes['removed_from_sharepoint_at'],
            'published_at' => $attributes['published_at'],
            'sharepoint_modified_at' => $attributes['sharepoint_modified_at'],
            'checked_at' => $attributes['checked_at'],
            'sync_status' => $attributes['sync_status'],
            'sync_error_message' => $attributes['sync_error_message'],
            'sync_attempts' => $attributes['sync_attempts'],
            'last_sync_attempt_at' => $attributes['last_sync_attempt_at'],
            'effective_date' => $attributes['effective_date'],
            'expiration_date' => $attributes['expiration_date'],
            'language_code' => $document->language_code,
            'institution' => $institution,
        ];
        if ($document->relationLoaded('tenant')) {
            $payload['tenant'] = $document->tenant->toArray();
        }

        return $payload;
    }

    /** @return list<array{id: int, title: string, name: string, status: DocumentStatus, content_type: string|null, language: string|null, document_date: string|null, institution: array{id: string, name: string, tenant_shortname: string|null}|null, sharepoint_institution_label: string|null, sharepoint_path: string|null, sharepoint_web_url: string|null, sharepoint_modified_at: string|null, removed_from_sharepoint_at: string|null, problems: list<'institution'|'unknown_institution'|'content_type'|'document_date'|'language'>, sync_status: 'pending'|'syncing'|'success'|'failed'|'imported', sync_error_message: string|null, public_url: string|null, can: array{update: bool}}> */
    public static function pendingDocuments(Meeting $meeting, User $user): array
    {
        $documents = [];
        foreach (PendingMeetingDocuments::query($meeting, $user)->limit(8)->get() as $document) {
            $documents[] = new DocumentRowResource($document)->resolve(request());
        }

        return $documents;
    }

    /** @return list<array{id: string, name: string|null, is_active: bool, tenant_shortname: string|null}> */
    public static function availableInstitutionsForAttach(Meeting $meeting, User $user): array
    {
        $institutions = [];
        foreach (GetAttachableMeetingInstitutions::execute($user, $meeting) as $institution) {
            $institutions[] = [
                'id' => $institution->id,
                'name' => $institution->name,
                'is_active' => (bool) $institution->is_active,
                'tenant_shortname' => $institution->tenant?->shortname,
            ];
        }

        return $institutions;
    }
}
