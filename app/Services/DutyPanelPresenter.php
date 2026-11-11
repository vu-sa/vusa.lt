<?php

namespace App\Services;

use App\Actions\Duties\BuildDutyLineage;
use App\Models\Duty;
use App\Models\FileableFile;
use App\Models\Pivots\Dutiable;
use App\Models\StudyProgram;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Support\Collection;

class DutyPanelPresenter
{
    /**
     * @param  Collection<int, FileableFile>  $files
     * @return list<array{id: string, fileable_type: string, fileable_id: string, sharepoint_id: string, sharepoint_path: string|null, name: string, file_type: string|null, mime_type: string|null, size_bytes: int|null, file_date: string|null, description: string|null, public_link: string|null, public_link_expires_at: string|null, last_synced_at: string|null, deleted_externally_at: string|null, created_at: string|null, updated_at: string|null, formatted_size: string|null}>
     */
    public static function files(Collection $files): array
    {
        $payload = [];
        foreach ($files as $file) {
            $item = FileableFilePresenter::file($file);
            $payload[] = $item;
        }

        return $payload;
    }

    /** @return list<array{id: string, name: string|null, institution_id: string, order: int, current_users: list<array{id: string, name: string, profile_photo_path: string|null, pivot: array{duty_id: string, dutiable_id: string, dutiable_type: string, id: string, via_dutiable_id: string|null, tenant_id: int|null, start_date: string, end_date: string|null, additional_photo: string|null, additional_photo_focal_point: string|null, additional_email: string|null, use_original_duty_name: bool, description: string|null, study_program_id: string|null, study_program_note: string|null, created_at: string, updated_at: string}}>}> */
    public static function otherDuties(Duty $duty): array
    {
        $payload = [];
        if ($duty->institution === null) {
            return $payload;
        }
        foreach ($duty->institution->duties()->where('id', '!=', $duty->id)->orderBy('order')
            ->with('current_users:id,name,profile_photo_path')->get(['id', 'name', 'institution_id', 'order']) as $sibling) {
            $users = [];
            foreach ($sibling->current_users as $user) {
                $item = UserPresenter::avatar($user);
                $attributes = $user->pivot->attributesToArray();
                $item['pivot'] = [
                    'duty_id' => $attributes['duty_id'],
                    'dutiable_id' => $attributes['dutiable_id'],
                    'dutiable_type' => $attributes['dutiable_type'],
                    'id' => $attributes['id'],
                    'via_dutiable_id' => $attributes['via_dutiable_id'],
                    'tenant_id' => $attributes['tenant_id'],
                    'start_date' => $attributes['start_date'],
                    'end_date' => $attributes['end_date'],
                    'additional_photo' => $attributes['additional_photo'],
                    'additional_photo_focal_point' => $attributes['additional_photo_focal_point'],
                    'additional_email' => $attributes['additional_email'],
                    'use_original_duty_name' => $attributes['use_original_duty_name'],
                    'description' => $attributes['description'] === null ? null : (string) $user->pivot->description,
                    'study_program_id' => $attributes['study_program_id'],
                    'study_program_note' => $attributes['study_program_note'] === null ? null : (string) $user->pivot->study_program_note,
                    'created_at' => $attributes['created_at'],
                    'updated_at' => $attributes['updated_at'],
                ];
                $users[] = $item;
            }
            $payload[] = [
                'id' => $sibling->id,
                'name' => $sibling->name === null ? null : (string) $sibling->name,
                'institution_id' => $sibling->institution_id,
                'order' => $sibling->order,
                'current_users' => $users,
            ];
        }

        return $payload;
    }

    /**
     * @param  Collection<int, StudyProgram>  $programs
     * @return list<array{id: string, name: string, degree: string, tenant_id: int, created_at: string, updated_at: string, deleted_at: string|null}>
     */
    public static function studyPrograms(Collection $programs): array
    {
        $payload = [];
        foreach ($programs as $program) {
            $attributes = $program->attributesToArray();
            $payload[] = [
                'id' => $program->id,
                'name' => (string) $program->name,
                'degree' => $program->degree,
                'tenant_id' => $program->tenant_id,
                'created_at' => $attributes['created_at'],
                'updated_at' => $attributes['updated_at'],
                'deleted_at' => $attributes['deleted_at'],
            ];
        }

        return $payload;
    }

    /** @return array{events: list<array{kind: 'rename', date: string, name: string|null}|array{kind: 'took_over', date: string, duties: list<array{id: string, link_id: int, name: string|null, is_active: bool, institution_name: string|null, can_fold: bool}>}|array{kind: 'handed_over', date: string, duties: list<array{id: string, link_id: int, name: string|null, is_active: bool, institution_name: string|null}>}>, predecessorTerms: list<array{id: string, name: string, profile_photo_path: string|null, pivot: array{id: string, start_date: string, end_date: string|null}, duty_name: string|null, via_duty_id: string}>, suggestedLinkDate: string} */
    public static function lineage(Duty $duty, ?User $user): array
    {
        return BuildDutyLineage::execute($duty, $user);
    }

    /** @return array{update: bool, managePeople: bool, assignQuota: bool, reviewExOfficio: bool} */
    public static function abilities(Duty $duty, ?User $user): array
    {
        return [
            'update' => (bool) $user?->can('update', $duty),
            'managePeople' => (bool) $user?->can('managePeople', $duty),
            'assignQuota' => (bool) $user?->can('assignQuota', [Dutiable::class, $duty]),
            'reviewExOfficio' => (bool) $user?->can('managePeople', $duty)
                && ($duty->exOfficioTargetDuties()->exists() || $duty->exOfficioSourceDuties()->exists()),
        ];
    }

    /**
     * The padaliniai the duty offers seats to, with how many current seats count toward each.
     *
     * @return list<array{id: int, shortname: string, quota: int|null, occupied: int}>
     */
    public static function quotaTenants(Duty $duty): array
    {
        $occupied = Dutiable::query()
            ->where('duty_id', $duty->id)
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->whereNotNull('tenant_id')
            ->current()
            ->selectRaw('tenant_id, count(*) as seats')
            ->groupBy('tenant_id')
            ->pluck('seats', 'tenant_id');

        /** @var \Illuminate\Database\Eloquent\Collection<int, Tenant> $tenants */
        $tenants = $duty->assignableTenants()->orderBy('shortname')->get(['tenants.id', 'tenants.shortname']);

        return $tenants->map(fn (Tenant $tenant) => [
            'id' => $tenant->id,
            'shortname' => $tenant->shortname,
            'quota' => $tenant->getAttribute('pivot')?->quota,
            'occupied' => (int) ($occupied[$tenant->id] ?? 0),
        ])->values()->all();
    }
}
