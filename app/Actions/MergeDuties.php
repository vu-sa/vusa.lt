<?php

namespace App\Actions;

use App\Actions\Dutiables\ReconcileExOfficioSeats;
use App\Listeners\HandleDutiableChange;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Support\MergeRepointer;
use App\Support\MorphMap;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Moves everything a duplicate duty carries onto the kept one, then deletes the duplicate permanently; DutyMergeCoverageTest keeps the maps complete. */
class MergeDuties
{
    /** Columns repointed as they are. */
    public const array REPOINT = [
        ['duty_names', 'duty_id'],
        ['goals', 'responsible_duty_id'],
    ];

    /** duty_id columns repointed only where the kept duty has no row sharing these columns. */
    public const array REPOINT_DEDUPED = [
        'duty_duty_type' => ['duty_type_id'],
        'duty_responsibilities' => ['responsibility', 'scope_type', 'scope_id'],
    ];

    /** Polymorphic owners: table => [morph name, columns shared with an existing kept row]. */
    public const array REPOINT_MORPH = [
        'activity_log' => ['subject', null],
        'comments' => ['commentable', null],
        'fileable_files' => ['fileable', null],
        'model_has_permissions' => ['model', ['permission_id']],
        'model_has_roles' => ['model', ['role_id']],
        'notifications' => ['notifiable', null],
        'responsibility_grants' => ['grantee', ['role_id', 'scope_type', 'scope_id']],
        'tasks' => ['taskable', null],
    ];

    /** Moved by their own rules below: assignments collapse, quotas keep the looser limit, self-links drop. */
    public const array CUSTOM = ['dutiables', 'duty_successions', 'duty_tenant', 'ex_officio_duties'];

    /**
     * @param  Collection<int, Duty>  $sources
     * @return array{moved_assignments: int, collapsed_assignments: int, moved_types: int, moved_roles: int, moved_ex_officio: int, moved_tenant_quotas: int, moved_former_names: int, moved_successions: int}
     */
    public static function execute(Duty $kept, Collection $sources, ?User $actor = null): array
    {
        return DB::transaction(function () use ($kept, $sources, $actor): array {
            $summary = ReconcileExOfficioSeats::paused(function () use ($kept, $sources, $actor): array {
                $sourceIds = $sources->pluck('id')->all();
                Institution::withTrashed()->whereIn('id', $sources->pluck('institution_id')->push($kept->institution_id))
                    ->orderBy('id')->lockForUpdate()->get();

                $movedAssignments = Dutiable::query()->whereIn('duty_id', $sourceIds)->count();
                // Model saves retain assignment events while reconciliation is paused.
                Dutiable::query()->whereIn('duty_id', $sourceIds)->each(fn (Dutiable $term) => $term->update(['duty_id' => $kept->id]));

                $summary = [
                    'moved_assignments' => $movedAssignments,
                    'collapsed_assignments' => CollapseOverlappingDutiables::execute($kept),
                    'moved_types' => 0,
                    'moved_roles' => 0,
                    'moved_ex_officio' => self::mergeExOfficioLinks($kept, $sourceIds),
                    'moved_tenant_quotas' => self::mergeDutyTenantQuotas($kept, $sourceIds),
                    'moved_former_names' => 0,
                    'moved_successions' => self::mergeSuccessions($kept, $sourceIds),
                ];
                $merged = [];

                foreach ($sources as $source) {
                    $moved = self::moveRows($kept, $source);
                    $summary['moved_types'] += $moved['duty_duty_type'];
                    $summary['moved_roles'] += $moved['responsibility_grants'];
                    $summary['moved_former_names'] += $moved['duty_names'];

                    $merged[] = ['id' => $source->id, 'name' => $source->getTranslations('name')];
                    $source->forceDelete();
                }

                ProjectDutyResponsibilities::forDuties([$kept->id]);

                activity()
                    ->performedOn($kept)
                    ->causedBy($actor)
                    ->event('duties_merged')
                    ->withProperties(['merged' => $merged, 'summary' => $summary])
                    ->log('duties_merged');

                // Holders already on the kept duty gain the moved roles without any dutiable event.
                $holderIds = $kept->dutiables()->where('dutiable_type', MorphMap::alias(User::class))->pluck('dutiable_id')->unique();

                DB::afterCommit(function () use ($kept, $holderIds): void {
                    $holderIds->each(fn (string $userId) => HandleDutiableChange::forgetUserAuthority($userId));
                    $kept->fresh()?->searchable();
                });

                return $summary;
            });
            ReconcileExOfficioSeats::forDuty($kept);

            return $summary;
        });
    }

    /** @return array<string, int> rows moved per table */
    private static function moveRows(Duty $kept, Duty $source): array
    {
        $moved = [];

        foreach (self::REPOINT as [$table, $column]) {
            $moved[$table] = MergeRepointer::plain($table, $column, $kept->id, $source->id);
        }

        foreach (self::REPOINT_DEDUPED as $table => $sharedColumns) {
            $moved[$table] = MergeRepointer::deduped($table, 'duty_id', $sharedColumns, $kept->id, $source->id);
        }

        foreach (self::REPOINT_MORPH as $table => [$morph, $sharedColumns]) {
            $moved[$table] = MergeRepointer::morph($table, $morph, $sharedColumns, MorphMap::alias(Duty::class), $kept->id, $source->id);
        }

        // Grouped entries (e.g. a term edited under its duty) point at their record through the root as well.
        MergeRepointer::morph('activity_log', 'root_subject', null, MorphMap::alias(Duty::class), $kept->id, $source->id);

        return $moved;
    }

    /**
     * Repoints predecessor links onto the kept duty; a link between two merged duties
     * would become a self-link and is dropped, as is any pair the kept duty already has.
     *
     * @param  list<string>  $sourceIds
     */
    private static function mergeSuccessions(Duty $kept, array $sourceIds): int
    {
        $rows = DB::table('duty_successions')
            ->whereIn('predecessor_id', $sourceIds)
            ->orWhereIn('successor_id', $sourceIds)
            ->get();

        $repoint = fn (string $id) => in_array($id, $sourceIds, true) ? $kept->id : $id;
        $moved = 0;

        foreach ($rows as $row) {
            $predecessorId = $repoint($row->predecessor_id);
            $successorId = $repoint($row->successor_id);

            if ($predecessorId !== $successorId) {
                $moved += DB::table('duty_successions')->insertOrIgnore([
                    'predecessor_id' => $predecessorId,
                    'successor_id' => $successorId,
                    'effective_on' => $row->effective_on,
                    'created_at' => $row->created_at,
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('duty_successions')->whereIn('id', $rows->pluck('id'))->delete();

        return $moved;
    }

    /**
     * duty_tenant is unique on (duty_id, tenant_id); a tenant assignable on both
     * the kept duty and a source keeps the more permissive quota — null means
     * unlimited, so null beats any finite number.
     */
    private static function mergeDutyTenantQuotas(Duty $kept, array $sourceIds): int
    {
        $moved = 0;

        foreach (DB::table('duty_tenant')->whereIn('duty_id', $sourceIds)->get() as $row) {
            $existing = DB::table('duty_tenant')->where('duty_id', $kept->id)->where('tenant_id', $row->tenant_id)->first();

            if ($existing) {
                DB::table('duty_tenant')->where('id', $existing->id)->update([
                    'quota' => ($existing->quota === null || $row->quota === null) ? null : max($existing->quota, $row->quota),
                ]);
            } else {
                DB::table('duty_tenant')->insert([
                    'duty_id' => $kept->id,
                    'tenant_id' => $row->tenant_id,
                    'quota' => $row->quota,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $moved++;
        }

        DB::table('duty_tenant')->whereIn('duty_id', $sourceIds)->delete();

        return $moved;
    }

    private static function mergeExOfficioLinks(Duty $kept, array $sourceIds): int
    {
        $moved = 0;

        foreach (DB::table('ex_officio_duties')->whereIn('source_duty_id', $sourceIds)->get() as $row) {
            if ($row->target_duty_id === $kept->id) {
                continue;
            }

            $exists = DB::table('ex_officio_duties')->where('source_duty_id', $kept->id)->where('target_duty_id', $row->target_duty_id)->exists();

            if (! $exists) {
                DB::table('ex_officio_duties')->where('id', $row->id)->update(['source_duty_id' => $kept->id]);
                $moved++;
            }
        }

        DB::table('ex_officio_duties')->whereIn('source_duty_id', $sourceIds)->delete();

        foreach (DB::table('ex_officio_duties')->whereIn('target_duty_id', $sourceIds)->get() as $row) {
            if ($row->source_duty_id === $kept->id) {
                continue;
            }

            $exists = DB::table('ex_officio_duties')->where('source_duty_id', $row->source_duty_id)->where('target_duty_id', $kept->id)->exists();

            if (! $exists) {
                DB::table('ex_officio_duties')->where('id', $row->id)->update(['target_duty_id' => $kept->id]);
                $moved++;
            }
        }

        DB::table('ex_officio_duties')->whereIn('target_duty_id', $sourceIds)->delete();

        return $moved;
    }
}
