<?php

namespace App\Services\Learning\SystemSteps;

use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BrowserPushDetector implements SystemStepDetector
{
    /**
     * @param  Collection<int, string>  $userIds
     * @return Collection<int, string>
     */
    public function doneFor(Collection $userIds): Collection
    {
        return DB::table('push_subscriptions')->whereIn('subscribable_id', $userIds)
            ->where('subscribable_type', MorphMap::alias(User::class))->distinct()->pluck('subscribable_id');
    }
}
