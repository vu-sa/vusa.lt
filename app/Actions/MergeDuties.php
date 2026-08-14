<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Merges one or more duplicate duties into a single kept duty.
 *
 * Sibling to MergeUsers and StudyProgramController::mergeStudyPrograms(), but a
 * duty carries far more than either: assignments (dutiables), cross-tenant
 * quotas (duty_tenant), ex-officio links (ex_officio_duties), content types and
 * and admin roles (typeables / model_has_roles). Every one of them is repointed
 * here, in a single transaction.
 * Sources are soft-deleted, not force-deleted — recoverable, like the other
 * merge actions in this app.
 */
class MergeDuties
{
    /**
     * @param  Collection<int, Duty>  $sources
     * @return array{moved_assignments: int, collapsed_assignments: int, moved_types: int, moved_roles: int, moved_ex_officio: int, moved_tenant_quotas: int}
     */
    public static function execute(Duty $kept, Collection $sources): array
    {
        return DB::transaction(function () use ($kept, $sources): array {
            $sourceIds = $sources->pluck('id')->all();

            $movedAssignments = Dutiable::query()->whereIn('duty_id', $sourceIds)->count();
            Dutiable::query()->whereIn('duty_id', $sourceIds)->update(['duty_id' => $kept->id]);

            $summary = [
                'moved_assignments' => $movedAssignments,
                'collapsed_assignments' => CollapseOverlappingDutiables::execute($kept),
                'moved_types' => self::repointMorphPivot('typeables', 'typeable_id', 'typeable_type', 'type_id', $kept, $sourceIds),
                'moved_roles' => self::repointMorphPivot('model_has_roles', 'model_id', 'model_type', 'role_id', $kept, $sourceIds),
                'moved_ex_officio' => self::mergeExOfficioLinks($kept, $sourceIds),
                'moved_tenant_quotas' => self::mergeDutyTenantQuotas($kept, $sourceIds),
            ];

            foreach ($sources as $source) {
                $source->delete();
            }

            $kept->searchable();

            return $summary;
        });
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

    /**
     * ex_officio_duties is unique on (source_duty_id, target_duty_id) in both
     * directions. A link that would become self-referencing once both ends
     * merge onto the same kept duty (source ex-officio target, or vice versa)
     * is simply dropped — "holding this duty grants itself" is meaningless.
     */
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

    /**
     * Shared shape for typeables and model_has_roles: a polymorphic
     * pivot keyed on (model id, model type, other id). Repoints a source's row
     * onto the kept duty unless the kept duty already has that same pairing, in
     * which case the source's row is a pure duplicate and is simply dropped.
     *
     * @param  list<string>  $sourceIds
     */
    private static function repointMorphPivot(
        string $table,
        string $modelIdColumn,
        string $modelTypeColumn,
        string $otherIdColumn,
        Duty $kept,
        array $sourceIds,
    ): int {
        $moved = 0;

        $rows = DB::table($table)->where($modelTypeColumn, Duty::class)->whereIn($modelIdColumn, $sourceIds)->get();

        foreach ($rows as $row) {
            $otherId = $row->{$otherIdColumn};

            $alreadyOnKept = DB::table($table)
                ->where($modelTypeColumn, Duty::class)
                ->where($modelIdColumn, $kept->id)
                ->where($otherIdColumn, $otherId)
                ->exists();

            if (! $alreadyOnKept) {
                DB::table($table)
                    ->where($modelTypeColumn, Duty::class)
                    ->where($modelIdColumn, $row->{$modelIdColumn})
                    ->where($otherIdColumn, $otherId)
                    ->update([$modelIdColumn => $kept->id]);
                $moved++;
            }
        }

        // Whatever still points at a source id at this point is an exact
        // duplicate of something the kept duty already has — spent.
        DB::table($table)->where($modelTypeColumn, Duty::class)->whereIn($modelIdColumn, $sourceIds)->delete();

        return $moved;
    }
}
