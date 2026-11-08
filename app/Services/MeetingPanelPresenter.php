<?php

namespace App\Services;

use App\Actions\Documents\PendingMeetingDocuments;
use App\Actions\GetAttachableMeetingInstitutions;
use App\Enums\DocumentStatus;
use App\Http\Resources\DocumentRowResource;
use App\Http\Resources\TaskResource;
use App\Models\Document;
use App\Models\Meeting;
use App\Models\Task;
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

    /**
     * @param  Collection<int, User>  $users
     * @return list<array{id: string, name: string, email: string|null, phone: string|null, facebook_url: string|null, pronouns: string|null, show_pronouns: bool, profile_photo_path: string|null, profile_photo_focal_point: string|null}>
     */
    public static function representatives(Collection $users): array
    {
        $representatives = [];
        foreach ($users as $user) {
            $representatives[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'facebook_url' => $user->facebook_url,
                'pronouns' => $user->getRawOriginal('pronouns') === null ? null : (string) $user->pronouns,
                'show_pronouns' => $user->show_pronouns,
                'profile_photo_path' => $user->profile_photo_path,
                'profile_photo_focal_point' => $user->profile_photo_focal_point,
            ];
        }

        return $representatives;
    }

    /** @return list<array{id: string, name: string, description: string|null, due_date: string|null, completed_at: string|null, cancelled_at: string|null, created_at: string, updated_at: string, action_type: ActionType|null, metadata: array<string, mixed>|null, progress: array{current: int, total: int, percentage: int}|null, is_overdue: bool, can_be_manually_completed: bool, can_update: bool, icon: string, color: string, taskable: array{id: int|string, name: string|null, type: string}|null, taskable_type: string, taskable_id: string, users: list<array{id: string, name: string, profile_photo_path: string|null}>}> */
    public static function tasks(Meeting $meeting): array
    {
        return self::taskRows(
            $meeting->tasks()->with('users:id,name,email,profile_photo_path', 'taskable', 'tenants')->get(),
            request()->user(),
        );
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return list<array{id: string, name: string, description: string|null, due_date: string|null, completed_at: string|null, cancelled_at: string|null, created_at: string, updated_at: string, action_type: ActionType|null, metadata: array<string, mixed>|null, progress: array{current: int, total: int, percentage: int}|null, is_overdue: bool, can_be_manually_completed: bool, can_update: bool, icon: string, color: string, taskable: array{id: int|string, name: string|null, type: string}|null, taskable_type: string, taskable_id: string, users: list<array{id: string, name: string, profile_photo_path: string|null}>}>
     */
    public static function taskRows(Collection $tasks, User $user): array
    {
        $rows = [];
        foreach ($tasks as $task) {
            $row = TaskResource::forListing($task, $user);
            unset($row['can_delete']);
            $rows[] = $row;
        }

        return $rows;
    }

    /** @return list<array{id: int, title: string, name: string, content_type: string|null, document_date: string|null, language_code: string, anonymous_url: string|null}> */
    public static function documents(Meeting $meeting, User $user): array
    {
        $documents = [];
        $linked = $meeting->documents()->orderBy('document_date')->orderBy('title')->get();
        foreach ($linked as $document) {
            if ($document->isPublished() || $user->can('view', $document)) {
                $documents[] = self::document($document);
            }
        }

        return $documents;
    }

    /** @return array{id: int, title: string, name: string, content_type: string|null, document_date: string|null, language_code: string, anonymous_url: string|null} */
    private static function document(Document $document): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'name' => $document->name,
            'content_type' => $document->content_type,
            'document_date' => $document->getRawOriginal('document_date'),
            'language_code' => $document->language_code,
            'anonymous_url' => $document->anonymous_url,
        ];
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
