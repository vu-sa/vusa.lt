<?php

namespace App\Actions\Dutiables;

use App\Models\Duty;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Reports rows on a target duty overlapping a source term by more than MIN_OVERLAP_DAYS.
 */
class FindSuspectExOfficioDutiables
{
    public static function query(?int $tenantId = null, ?array $targetDutyIds = null): Builder
    {
        $targetDutyIds ??= Duty::query()
            ->whereHas('exOfficioSourceDuties')
            ->pluck('id')
            ->all();
        $plan = PlanExOfficioReview::execute($targetDutyIds);
        $ids = $plan['candidate_ids'];
        $query = DB::table('dutiables as t')->whereIn('t.id', $ids)
            ->join('users as u', 'u.id', '=', 't.dutiable_id')
            ->join('duties as target', 'target.id', '=', 't.duty_id')
            ->join('ex_officio_duties as eo', 'eo.target_duty_id', '=', 't.duty_id')
            ->join('duties as source', 'source.id', '=', 'eo.source_duty_id')
            ->join('institutions as inst', 'inst.id', '=', 'target.institution_id')
            ->leftJoin('tenants as tn', 'tn.id', '=', 'inst.tenant_id')
            ->where(function (Builder $query) use ($plan): void {
                foreach ($plan['matches'] as $match) {
                    $query->orWhere(fn (Builder $pair) => $pair
                        ->where('t.id', $match['row_id'])
                        ->where('source.id', $match['source_duty_id']));
                }
            });
        if ($tenantId !== null) {
            $query->where('inst.tenant_id', $tenantId);
        }

        return $query;
    }

    public static function idsForDuties(array $targetDutyIds): array
    {
        if ($targetDutyIds === []) {
            return [];
        }

        return PlanExOfficioReview::execute($targetDutyIds)['candidate_ids'];
    }
}
