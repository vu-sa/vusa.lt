<?php

namespace App\Services\Learning\SystemSteps;

use App\Models\Meeting;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecordedMeetingDetector implements SystemStepDetector
{
    /**
     * @param  Collection<int, string>  $userIds
     * @return Collection<int, string>
     */
    public function doneFor(Collection $userIds): Collection
    {
        return DB::table('activity_log')->whereIn('causer_id', $userIds)
            ->where('causer_type', MorphMap::alias(User::class))
            ->where('subject_type', MorphMap::alias(Meeting::class))
            ->where('event', 'created')->distinct()->pluck('causer_id');
    }
}
