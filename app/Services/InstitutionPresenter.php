<?php

namespace App\Services;

use App\Actions\ActivityRequests\GetInstitutionActivityRequestHistory;
use App\Actions\GetInstitutionSecretaries;
use App\Actions\GetTypeFiles;
use App\Enums\CommentKind;
use App\Enums\InstitutionActivityAnswer;
use App\Enums\InstitutionActivityCampaign;
use App\Enums\InstitutionActivityStatus;
use App\Enums\InstitutionRelationKind;
use App\Enums\InstitutionScope;
use App\Enums\MeetingType;
use App\Http\Controllers\Admin\CadenceController;
use App\Http\Controllers\Admin\InstitutionSecretaryController;
use App\Http\Resources\InstitutionMeetingResource;
use App\Http\Resources\ProblemSummaryResource;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionLink;
use App\Models\Meeting;
use App\Models\Problem;
use App\Models\StudyProgram;
use App\Models\Task;
use App\Models\User;
use App\Services\ResourceServices\SharepointFileService;
use App\Settings\CadenceSettings;
use App\Support\MorphMap;
use App\Tasks\Enums\ActionType;
use App\ValueObjects\InstitutionActivityStatusData;
use Illuminate\Support\Collection;

class InstitutionPresenter
{
    /** @return array{id: string, name: string, is_active: bool, short_name: string|null, description: string|null, tenant: array{id: int, shortname: string}|null, types: list<array{id: int, parent_id: int|null, title: string|null, description: string|null, slug: string|null, extra_attributes: array<array-key, mixed>|null, created_at: string|null, updated_at: string|null, deleted_at: string|null, pivot: array{institution_id: string, institution_type_id: int}}>, has_public_meetings: bool, meeting_periodicity_days: int, governance_scope: InstitutionScope, comments_count: int, duties_count: int, meetings_count: int, problems_count: int, tasks_count: int, related_institutions_count: int, managers: list<array{id: string, email: string|null, phone: string|null, facebook_url: string|null, name: string, pronouns: string|null, show_pronouns: bool, is_active: int, updated_at: string, created_at: string, profile_photo_path: string|null, profile_photo_focal_point: string|null, deleted_at: string|null, pivot: array{duty_id: string, dutiable_id: string, dutiable_type: string, id: string, via_dutiable_id: string|null, tenant_id: int|null, start_date: string, end_date: string|null, additional_photo: string|null, additional_photo_focal_point: string|null, additional_email: string|null, use_original_duty_name: bool, description: string|null, study_program_id: string|null, study_program_note: string|null, created_at: string, updated_at: string}}>, secretaries: list<array{id: string, name: string, email: string|null, profile_photo_path: string|null}>, sharepointPath: string|null, sharepointFolderUrl: string|null} */
    public static function forShow(Institution $institution, bool $readOnly): array
    {
        $types = [];
        foreach ($institution->types as $type) {
            $types[] = DutyPresenter::institutionType($type);
        }
        $managers = [];
        foreach ($institution->managers() as $manager) {
            $managers[] = self::member($manager);
        }

        return [
            'id' => $institution->id,
            'name' => trim((string) $institution->name) ?: __('Be pavadinimo').' · '.($institution->alias ?: $institution->id),
            'is_active' => (bool) $institution->is_active,
            'short_name' => $institution->short_name,
            'description' => $institution->description,
            'tenant' => $institution->tenant ? ['id' => $institution->tenant->id, 'shortname' => $institution->tenant->shortname] : null,
            'types' => $types,
            'has_public_meetings' => $institution->has_public_meetings,
            'meeting_periodicity_days' => $institution->meeting_periodicity_days,
            'governance_scope' => $institution->governance_scope,
            'comments_count' => (int) $institution->getAttribute('comments_count'),
            'duties_count' => (int) $institution->getAttribute('duties_count'),
            'meetings_count' => (int) $institution->getAttribute('meetings_count'),
            'problems_count' => (int) $institution->getAttribute('problems_count'),
            'tasks_count' => (int) $institution->getAttribute('tasks_count') + (int) $institution->getAttribute('tasks_from_meetings_count'),
            'related_institutions_count' => app(InstitutionRelationService::class)->relatedTo($institution->id)->count(),
            'managers' => $managers,
            'secretaries' => $readOnly ? [] : InstitutionSecretaryController::usersPayload(GetInstitutionSecretaries::execute($institution)),
            'sharepointPath' => $readOnly ? null : SharepointFileService::pathOrNull($institution),
            'sharepointFolderUrl' => $readOnly ? null : SharepointFileService::folderUrlOrNull($institution),
        ];
    }

    /** @return array{id: string, email: string|null, phone: string|null, facebook_url: string|null, name: string, pronouns: string|null, show_pronouns: bool, is_active: int, updated_at: string, created_at: string, profile_photo_path: string|null, profile_photo_focal_point: string|null, deleted_at: string|null, pivot: array{duty_id: string, dutiable_id: string, dutiable_type: string, id: string, via_dutiable_id: string|null, tenant_id: int|null, start_date: string, end_date: string|null, additional_photo: string|null, additional_photo_focal_point: string|null, additional_email: string|null, use_original_duty_name: bool, description: string|null, study_program_id: string|null, study_program_note: string|null, created_at: string, updated_at: string}} */
    public static function member(User $user): array
    {
        $payload = UserPresenter::user($user);
        $pivot = $user->pivot->attributesToArray();
        $payload['pivot'] = [
            'duty_id' => $pivot['duty_id'],
            'dutiable_id' => $pivot['dutiable_id'],
            'dutiable_type' => $pivot['dutiable_type'],
            'id' => $pivot['id'],
            'via_dutiable_id' => $pivot['via_dutiable_id'],
            'tenant_id' => $pivot['tenant_id'],
            'start_date' => $pivot['start_date'],
            'end_date' => $pivot['end_date'],
            'additional_photo' => $pivot['additional_photo'],
            'additional_photo_focal_point' => $pivot['additional_photo_focal_point'],
            'additional_email' => $pivot['additional_email'],
            'use_original_duty_name' => $pivot['use_original_duty_name'],
            'description' => $user->pivot->getRawOriginal('description') === null ? null : (string) $user->pivot->description,
            'study_program_id' => $pivot['study_program_id'],
            'study_program_note' => $user->pivot->getRawOriginal('study_program_note') === null ? null : (string) $user->pivot->study_program_note,
            'created_at' => $pivot['created_at'],
            'updated_at' => $pivot['updated_at'],
        ];

        return $payload;
    }

    /** @return list<array{id: string, name: string|null, description: string|null, institution_id: string, order: int, email: string|null, contacts_grouping: string, places_to_occupy: int|null, is_active: bool, created_at: string, updated_at: string, deleted_at: string|null, current_users: list<array{id: string, email: string|null, phone: string|null, facebook_url: string|null, name: string, pronouns: string|null, show_pronouns: bool, is_active: int, updated_at: string, created_at: string, profile_photo_path: string|null, profile_photo_focal_point: string|null, deleted_at: string|null, pivot: array{duty_id: string, dutiable_id: string, dutiable_type: string, id: string, via_dutiable_id: string|null, tenant_id: int|null, start_date: string, end_date: string|null, additional_photo: string|null, additional_photo_focal_point: string|null, additional_email: string|null, use_original_duty_name: bool, description: string|null, study_program_id: string|null, study_program_note: string|null, created_at: string, updated_at: string}}>}> */
    public static function duties(Institution $institution): array
    {
        $duties = [];
        foreach ($institution->duties()->with('current_users')->orderBy('order')->get() as $duty) {
            $attributes = $duty->attributesToArray();
            $users = [];
            foreach ($duty->current_users as $user) {
                $users[] = self::member($user);
            }
            $duties[] = [
                'id' => $attributes['id'],
                'name' => $duty->getRawOriginal('name') === null ? null : (string) $duty->name,
                'description' => $duty->getRawOriginal('description') === null ? null : (string) $duty->description,
                'institution_id' => $attributes['institution_id'],
                'order' => $attributes['order'],
                'email' => $attributes['email'],
                'contacts_grouping' => $attributes['contacts_grouping'],
                'places_to_occupy' => $attributes['places_to_occupy'],
                'is_active' => $attributes['is_active'],
                'created_at' => $attributes['created_at'],
                'updated_at' => $attributes['updated_at'],
                'deleted_at' => $attributes['deleted_at'],
                'current_users' => $users,
            ];
        }

        return $duties;
    }

    /** @return list<array{id: string, title: string, start_time: string, type: MeetingType|null, agenda_items_count: int, agenda_item_titles: list<string>, vote_matches: int, vote_mismatches: int, incomplete_vote_data: int, has_protocol: bool, has_report: bool, can_discard: bool}> */
    public static function meetings(Collection $meetings): array
    {
        $rows = [];
        foreach ($meetings as $meeting) {
            $row = new InstitutionMeetingResource($meeting)->resolve(request());
            $rows[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'start_time' => $row['start_time'],
                'type' => $meeting->type,
                'agenda_items_count' => $row['agenda_items_count'],
                'agenda_item_titles' => $row['agenda_item_titles'],
                'vote_matches' => $row['vote_matches'],
                'vote_mismatches' => $row['vote_mismatches'],
                'incomplete_vote_data' => $row['incomplete_vote_data'],
                'has_protocol' => $row['has_protocol'],
                'has_report' => $row['has_report'],
                'can_discard' => $row['can_discard'],
            ];
        }

        return $rows;
    }

    /** @return array{activity_status: array{status: InstitutionActivityStatus, requires_action: bool, priority: int, periodicity_days: int, effective_days_since_activity: int|null, progress_percentage: int|null, last_activity_type: 'meeting'|'check_in'|null, last_activity_at: string|null, last_meeting_at: string|null, next_meeting_at: string|null, active_check_in_until: string|null, last_completed_check_in_at: string|null}|null, current_users: list<array{id: string, email: string|null, phone: string|null, facebook_url: string|null, name: string, pronouns: string|null, show_pronouns: bool, is_active: int, updated_at: string, created_at: string, profile_photo_path: string|null, profile_photo_focal_point: string|null, deleted_at: string|null, pivot: array{duty_id: string, dutiable_id: string, dutiable_type: string, id: string, via_dutiable_id: string|null, tenant_id: int|null, start_date: string, end_date: string|null, additional_photo: string|null, additional_photo_focal_point: string|null, additional_email: string|null, use_original_duty_name: bool, description: string|null, study_program_id: string|null, study_program_note: string|null, created_at: string, updated_at: string}}>, duties: list<array{id: string, name: string|null, order: int, current_users: list<array{id: string, email: string|null, phone: string|null, facebook_url: string|null, name: string, pronouns: string|null, show_pronouns: bool, is_active: int, updated_at: string, created_at: string, profile_photo_path: string|null, profile_photo_focal_point: string|null, deleted_at: string|null, pivot: array{duty_id: string, dutiable_id: string, dutiable_type: string, id: string, via_dutiable_id: string|null, tenant_id: int|null, start_date: string, end_date: string|null, additional_photo: string|null, additional_photo_focal_point: string|null, additional_email: string|null, use_original_duty_name: bool, description: string|null, study_program_id: string|null, study_program_note: string|null, created_at: string, updated_at: string}}>}>, recentMeetings: list<array{id: string, title: string, start_time: string, type: MeetingType|null, agenda_items_count: int, agenda_item_titles: list<string>, vote_matches: int, vote_mismatches: int, incomplete_vote_data: int, has_protocol: bool, has_report: bool, can_discard: bool}>, meetings_count: int, meetings_hidden: bool, recentComments: list<array{id: string, body: string, kind: CommentKind, created_at: string|null, replies_count: int, user: array{id: string, name: string, profile_photo_path: string|null}|null}>} */
    public static function overview(Institution $institution, bool $readOnly, bool $showsMeetings, InstitutionActivityStatusData $status, Collection $meetings): array
    {
        $activity = $status->toArray();
        $activity['status'] = $status->status;
        $users = [];
        foreach ($institution->duties->pluck('current_users')->flatten()->unique('id')->values() as $user) {
            $users[] = self::member($user);
        }
        $duties = [];
        foreach ($institution->duties->sortBy('order') as $duty) {
            $members = [];
            foreach ($duty->current_users as $user) {
                $members[] = self::member($user);
            }
            $duties[] = ['id' => $duty->id, 'name' => $duty->name, 'order' => $duty->order, 'current_users' => $members];
        }
        $comments = [];
        if (! $readOnly) {
            foreach ($institution->comments()->roots()->notErased()->with('user:id,name,profile_photo_path')
                ->withCount(['replies' => fn ($query) => $query->notErased()])->latest()->limit(3)->get() as $comment) {
                $comments[] = [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'kind' => $comment->kind,
                    'created_at' => $comment->created_at?->toISOString(),
                    'replies_count' => (int) $comment->getAttribute('replies_count'),
                    'user' => $comment->user ? UserPresenter::avatar($comment->user) : null,
                ];
            }
        }

        return [
            'activity_status' => $showsMeetings ? $activity : null,
            'current_users' => $users,
            'duties' => $duties,
            'recentMeetings' => self::meetings($meetings),
            'meetings_count' => $showsMeetings ? (int) $institution->getAttribute('meetings_count') : 0,
            'meetings_hidden' => ! $showsMeetings && $institution->meetings_count > 0,
            'recentComments' => $comments,
        ];
    }

    /** @return list<array{id: string, name: string, description: string|null, due_date: string|null, completed_at: string|null, cancelled_at: string|null, created_at: string, updated_at: string, action_type: ActionType|null, metadata: array<string, mixed>|null, progress: array{current: int, total: int, percentage: int}|null, is_overdue: bool, can_be_manually_completed: bool, can_update: bool, icon: string, color: string, taskable: array{id: int|string, name: string|null, type: string}|null, taskable_type: string, taskable_id: string, users: list<array{id: string, name: string, profile_photo_path: string|null}>}> */
    public static function tasks(Institution $institution): array
    {
        $tasks = $institution->tasks()->with('users:id,name,email,profile_photo_path', 'taskable', 'tenants')->get()
            ->merge(Task::query()->where('taskable_type', MorphMap::alias(Meeting::class))
                ->whereIn('taskable_id', $institution->meetings()->select('meetings.id'))
                ->with('users:id,name,email,profile_photo_path', 'taskable', 'tenants')->get())
            ->sortByDesc('created_at')->values();

        return MeetingPanelPresenter::taskRows($tasks, request()->user());
    }

    /** @return list<array{id: string, fileable_type: string, fileable_id: string, sharepoint_id: string, sharepoint_path: string|null, name: string, file_type: string|null, mime_type: string|null, size_bytes: int|null, file_date: string|null, description: string|null, public_link: string|null, public_link_expires_at: string|null, last_synced_at: string|null, deleted_externally_at: string|null, created_at: string|null, updated_at: string|null, formatted_size: string|null}> */
    public static function files(Institution $institution): array
    {
        $rows = [];
        foreach ($institution->availableFiles()->orderByDesc('file_date')->get() as $file) {
            $row = FileableFilePresenter::file($file);
            $rows[] = $row;
        }

        return $rows;
    }

    /** @return list<array{id: string, fileable_type: string, fileable_id: string, sharepoint_id: string, sharepoint_path: string|null, name: string, file_type: string|null, mime_type: string|null, size_bytes: int|null, file_date: string|null, description: string|null, public_link: string|null, public_link_expires_at: string|null, last_synced_at: string|null, deleted_externally_at: string|null, created_at: string|null, updated_at: string|null, formatted_size: string|null, fileable: array{id: int, title: string|null}|null}> */
    public static function typeFiles(Institution $institution): array
    {
        $rows = [];
        foreach (GetTypeFiles::forFileable($institution) as $file) {
            $row = FileableFilePresenter::file($file);
            $row['fileable'] = $file->fileable ? ['id' => $file->fileable->id, 'title' => $file->fileable->getRawOriginal('title') === null ? null : (string) $file->fileable->title] : null;
            $rows[] = $row;
        }

        return $rows;
    }

    /** @return list<array{id: string, title: string, status: string, occurred_at: string, resolved_at: string|null, tenant: array{id: int, shortname: string}|null, responsible_user: array{id: string, name: string}|null}> */
    public static function problems(Institution $institution): array
    {
        $rows = [];
        foreach ($institution->problems()->with(['tenant:id,shortname', 'responsibleUser:id,name'])
            ->orderByRaw("status = 'resolved'")->orderByDesc('occurred_at')->get() as $problem) {
            $row = new ProblemSummaryResource($problem)->resolve(request());
            $rows[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'status' => $problem->status,
                'occurred_at' => $row['occurred_at'],
                'resolved_at' => $row['resolved_at'],
                'tenant' => $row['tenant'],
                'responsible_user' => $row['responsible_user'],
            ];
        }

        return $rows;
    }

    /** @return list<array{id: string, name: string|null, is_active: bool, direction: 'outgoing'|'incoming'|'mutual', via: 'direct'|'type', kind: InstitutionRelationKind, kind_label: string, cross_tenant: bool, authorized: bool}> */
    public static function relatedInstitutions(Institution $institution): array
    {
        $rows = [];
        foreach (app(InstitutionRelationService::class)->summaryFor($institution) as $row) {
            $rows[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'is_active' => $row['is_active'],
                'direction' => $row['direction'],
                'via' => $row['via'],
                'kind' => InstitutionRelationKind::from($row['kind']),
                'kind_label' => $row['kind_label'],
                'cross_tenant' => $row['cross_tenant'],
                'authorized' => $row['authorized'],
            ];
        }

        return $rows;
    }

    /** @return list<array{id: int, created_at: string|null, updated_at: string|null, other: array{id: string, name: string|null, is_active: bool}, direction: 'outgoing'|'incoming', kind: InstitutionRelationKind, kind_label: string, mutual: bool}> */
    public static function institutionLinks(Institution $institution): array
    {
        $rows = [];
        foreach (app(InstitutionRelationService::class)->linksOf($institution) as $row) {
            $rows[] = [
                'id' => $row['id'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'other' => $row['other'],
                'direction' => $row['direction'],
                'kind' => InstitutionRelationKind::from($row['kind']),
                'kind_label' => $row['kind_label'],
                'mutual' => $row['mutual'],
            ];
        }

        return $rows;
    }

    /** @return array{cadences: list<array{id: string, institution_id: string|null, institution_name: string|null, start_date: string, end_date: string, start_meeting: array{id: string, title: string, start_time: string, institution_id: string|null, institution_name: string|null}|null, end_meeting: array{id: string, title: string, start_time: string, institution_id: string|null, institution_name: string|null}|null, label: string}>, globalCadences: list<array{id: string, institution_id: string|null, institution_name: string|null, start_date: string, end_date: string, start_meeting: array{id: string, title: string, start_time: string, institution_id: string|null, institution_name: string|null}|null, end_meeting: array{id: string, title: string, start_time: string, institution_id: string|null, institution_name: string|null}|null, label: string}>, cadenceDefaults: array{default_start_month_day: string, default_end_month_day: string}, secretaryRosters: list<array{cadence_id: string, label: string, start_date: string, end_date: string, is_global: bool, is_current: bool, is_past: bool, secretaries: list<array{id: string, name: string, email: string|null, profile_photo_path: string|null}>}>, studyPrograms: list<array{id: string, name: string, degree: string, tenant_id: int}>} */
    public static function management(Institution $institution): array
    {
        $programs = [];
        foreach (StudyProgram::query()->where('tenant_id', $institution->tenant_id)->get(['id', 'name', 'degree', 'tenant_id']) as $program) {
            $programs[] = ['id' => $program->id, 'name' => $program->name, 'degree' => $program->degree, 'tenant_id' => $program->tenant_id];
        }

        return [
            'cadences' => CadenceController::payload($institution->id),
            'globalCadences' => CadenceController::payload(globalOnly: true),
            'cadenceDefaults' => [
                'default_start_month_day' => app(CadenceSettings::class)->default_start_month_day,
                'default_end_month_day' => app(CadenceSettings::class)->default_end_month_day,
            ],
            'secretaryRosters' => InstitutionSecretaryController::payload($institution),
            'studyPrograms' => $programs,
        ];
    }

    /** @return list<array{value: InstitutionRelationKind, label: string}> */
    public static function relationKinds(bool $allowed): array
    {
        $options = [];
        if ($allowed) {
            foreach (InstitutionRelationKind::cases() as $kind) {
                $options[] = ['value' => $kind, 'label' => $kind->label()];
            }
        }

        return $options;
    }

    /** @return array{update: bool, delete: bool, recordMeeting: bool, createProblem: bool, reportActivity: bool, manageLinks: bool, askAboutActivity: bool} */
    public static function abilities(Institution $institution, User $user, bool $readOnly): array
    {
        return [
            'update' => $user->can('update', $institution),
            'delete' => $user->can('delete', $institution),
            'recordMeeting' => ! $readOnly && $user->can('createFor', [Meeting::class, $institution]),
            'createProblem' => $user->can('create', Problem::class),
            'reportActivity' => ! $readOnly && $user->can('create', [InstitutionCheckIn::class, $institution]),
            'manageLinks' => ! $readOnly && $user->can('create', InstitutionLink::class),
            'askAboutActivity' => ! $readOnly && $user->can('askAboutActivity', [InstitutionCheckIn::class, $institution]),
        ];
    }

    /** @return array{is_followed: bool, is_duty_based: bool}|null */
    public static function subscription(Institution $institution, User $user): ?array
    {
        $followed = $user->follows($institution);

        return $followed || $user->can('follow', $institution)
            ? ['is_followed' => $followed, 'is_duty_based' => $user->hasInstitution($institution)]
            : null;
    }

    /** @return array{data: list<array{id: string, campaigns: array<string, list<array{id: string, campaign_type: InstitutionActivityCampaign, requester: string|null, automatic: bool, recipient: string|null, period_start: string, period_end: string|null, legacy: bool, note: string|null, answer: InstitutionActivityAnswer|null, created_at: string|null, answered_at: string|null, resolved_at: string|null, expires_at: string, resolution_source: string|null, resolved_by: string|null, status: 'pending'|'answered'|'resolved'|'expired', meetings: list<array{id: string, date: string, url: string|null}>, check_ins: list<array{id: string, start: string, end: string}>}>>}>, next_page: int|null} */
    public static function activityRequests(Institution $institution, User $user, int $page): array
    {
        $history = app(GetInstitutionActivityRequestHistory::class)->execute($institution, $user, $page);
        $batches = [];
        foreach ($history['data'] as $batch) {
            $campaigns = [];
            foreach ($batch['campaigns'] as $campaign => $requests) {
                $rows = [];
                foreach ($requests as $row) {
                    $rows[] = [
                        'id' => $row['id'],
                        'campaign_type' => InstitutionActivityCampaign::from($row['campaign_type']),
                        'requester' => $row['requester'],
                        'automatic' => $row['automatic'],
                        'recipient' => $row['recipient'],
                        'period_start' => $row['period_start'],
                        'period_end' => $row['period_end'],
                        'legacy' => $row['legacy'],
                        'note' => $row['note'],
                        'answer' => $row['answer'] === null ? null : InstitutionActivityAnswer::from($row['answer']),
                        'created_at' => $row['created_at'],
                        'answered_at' => $row['answered_at'],
                        'resolved_at' => $row['resolved_at'],
                        'expires_at' => $row['expires_at'],
                        'resolution_source' => $row['resolution_source'],
                        'resolved_by' => $row['resolved_by'],
                        'status' => $row['status'],
                        'meetings' => $row['meetings'],
                        'check_ins' => $row['check_ins'],
                    ];
                }
                $campaigns[$campaign] = $rows;
            }
            $batches[] = ['id' => $batch['id'], 'campaigns' => $campaigns];
        }

        return ['data' => $batches, 'next_page' => $history['next_page']];
    }
}
