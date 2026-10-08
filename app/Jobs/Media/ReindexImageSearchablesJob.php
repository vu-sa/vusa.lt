<?php

namespace App\Jobs\Media;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Institution;
use App\Models\News;
use App\Models\PublicInstitution;
use App\Models\PublicNews;
use App\Models\Resource;
use App\Services\ContentResolution\ContentPartResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/**
 * Upserts the documents whose image URLs the backfill changed and forgets cached URLs. Never
 * search:reindex, which drops and recreates every collection.
 *
 * Transitional(legacy-images): remove when media:backfill-legacy-images --status shows 0 pending.
 */
class ReindexImageSearchablesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct()
    {
        $this->onConnection('long-running');
    }

    public function handle(): void
    {
        foreach ([News::class, PublicNews::class, Institution::class, PublicInstitution::class, Resource::class] as $model) {
            $model::query()->searchable();
        }

        // Caches that hold image URLs; the backfill fires no owner events to forget them.
        Cache::forget(HandleInertiaRequests::TENANTS_CACHE_KEY);
        Cache::tags(['banners'])->flush();
        Cache::tags([ContentPartResolver::CACHE_TAG])->flush();
    }
}
