<?php

namespace App\Listeners;

use App\Events\SearchRelationChanged;
use App\Models\Institution;
use App\Models\Meeting;
use App\Services\RepStatisticsCache;

/**
 * Syncing a relation fires no model event. A meeting's institutions carry no detached ids, so its old
 * padalinys cannot be told from here: clear every entry (a rare edit). An institution's types
 * decide which bodies count, and it stays in one padalinys.
 */
class InvalidateRepStatisticsRelation
{
    public function handle(SearchRelationChanged $event): void
    {
        if ($event->model instanceof Meeting && $event->relation === 'institutions') {
            RepStatisticsCache::invalidate();
        }

        if ($event->model instanceof Institution && $event->relation === 'types') {
            RepStatisticsCache::invalidate(tenantIds: [$event->model->tenant_id]);
        }
    }
}
