<?php

use App\Actions\CollapseOverlappingDutiables;
use App\Models\Duty;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time cleanup of duplicate active dutiable rows.
 *
 * Some duties (notably through past merges and the duty-user wizard) carry two
 * concurrently-active rows for the same holder under the same tenant scope —
 * an impossibility a person can only hold once at a time. The remaining
 * cross-tenant pairs (same person, different tenant_id) are legitimate
 * assignment scopes and are left untouched, because CollapseOverlappingDutiables
 * groups by tenant_id and so never folds them together.
 *
 * Only duties that actually have a duplicate active group are visited, so an
 * already-clean database pays nothing.
 */
return new class extends Migration
{
    /**
     * @return array<int, string>
     */
    private function duplicateActiveDutyIds(): array
    {
        return DB::table('dutiables')
            ->select('duty_id')
            ->where('dutiable_type', 'App\\Models\\User')
            ->where(function ($query): void {
                $query->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->groupBy('duty_id', 'dutiable_id', 'dutiable_type', 'tenant_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('duty_id')
            ->unique()
            ->all();
    }

    public function up(): void
    {
        foreach ($this->duplicateActiveDutyIds() as $dutyId) {
            /** @var Duty|null $duty */
            $duty = Duty::find($dutyId);

            if ($duty) {
                CollapseOverlappingDutiables::execute($duty);
            }
        }
    }

    public function down(): void
    {
        // Data cleanup — no schema to reverse.
    }
};
