<?php

namespace App\Services\Learning\SystemSteps;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WriteHandoverDetector implements SystemStepDetector
{
    /**
     * @param  Collection<int, string>  $userIds
     * @return Collection<int, string>
     */
    public function doneFor(Collection $userIds): Collection
    {
        return DB::table('handover_notes')->whereIn('user_id', $userIds)
            ->distinct()->pluck('user_id');
    }
}
