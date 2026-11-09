<?php

namespace App\Services\Learning\SystemSteps;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationSettingsDetector implements SystemStepDetector
{
    /**
     * @param  Collection<int, string>  $userIds
     * @return Collection<int, string>
     */
    public function doneFor(Collection $userIds): Collection
    {
        return DB::table('users')->whereIn('id', $userIds)
            ->whereNotNull('notification_preferences')->distinct()->pluck('id');
    }
}
