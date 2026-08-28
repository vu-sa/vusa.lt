<?php

namespace App\Actions\Dutiables;

use App\Console\Commands\AuditExOfficioDutiables;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Active rows on an ex-officio target duty whose holder no longer holds the source duty
 * that grants it.
 *
 * These are only ever *reported*. `dutiables.via_dutiable_id` is `nullOnDelete()`, so a
 * row whose link is gone is indistinguishable from a deliberate manual assignment — and
 * they grant real permissions, so guessing wrong takes access away from someone.
 *
 * Extracted from {@see AuditExOfficioDutiables} so the timeline's
 * diagnostics flag exactly the same rows the command reports, rather than a second
 * definition that drifts from it.
 */
class FindSuspectExOfficioDutiables
{
    /**
     * @param  list<string>|null  $targetDutyIds  restricts the sweep to the duties on screen
     */
    public static function query(?int $tenantId = null, ?array $targetDutyIds = null): Builder
    {
        $today = now()->toDateString();

        $query = DB::table('ex_officio_duties as eo')
            ->join('dutiables as t', function ($join): void {
                $join->on('t.duty_id', '=', 'eo.target_duty_id')
                    ->where('t.dutiable_type', '=', MorphMap::alias(User::class))
                    ->whereNull('t.via_dutiable_id');
            })
            ->join('users as u', 'u.id', '=', 't.dutiable_id')
            ->join('duties as target', 'target.id', '=', 'eo.target_duty_id')
            ->join('duties as source', 'source.id', '=', 'eo.source_duty_id')
            ->leftJoin('institutions as inst', 'inst.id', '=', 'target.institution_id')
            ->leftJoin('tenants as tn', 'tn.id', '=', 'inst.tenant_id')
            ->whereNull('u.deleted_at')
            ->where(fn ($q) => $q->whereNull('t.end_date')->orWhere('t.end_date', '>=', $today))
            // The holder does not currently hold the source duty that grants this one.
            ->whereNotExists(function ($sub) use ($today): void {
                $sub->select(DB::raw('1'))
                    ->from('dutiables as s')
                    ->whereColumn('s.duty_id', 'eo.source_duty_id')
                    ->whereColumn('s.dutiable_id', 't.dutiable_id')
                    ->where('s.dutiable_type', '=', MorphMap::alias(User::class))
                    ->where(fn ($q) => $q->whereNull('s.end_date')->orWhere('s.end_date', '>=', $today));
            });

        if ($tenantId !== null) {
            $query->where('inst.tenant_id', $tenantId);
        }

        if ($targetDutyIds !== null) {
            $query->whereIn('eo.target_duty_id', $targetDutyIds);
        }

        return $query;
    }

    /**
     * @param  list<string>  $targetDutyIds
     * @return list<string> dutiable ids
     */
    public static function idsForDuties(array $targetDutyIds): array
    {
        if ($targetDutyIds === []) {
            return [];
        }

        return self::query(targetDutyIds: $targetDutyIds)->pluck('t.id')->all();
    }
}
