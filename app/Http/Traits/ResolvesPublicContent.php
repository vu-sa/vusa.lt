<?php

namespace App\Http\Traits;

use App\Models\Content;
use App\Models\ContentPart;
use App\Services\ContentResolution\ContentPartResolver;
use App\Services\ContentResolution\ResolutionContext;
use Illuminate\Support\Facades\Cache;

/**
 * Mixed into `PublicController` so every public page controller can resolve its rich
 * content's dynamic blocks (link-list, event-list, the news/calendar bridge) the same
 * way, without each controller re-deriving the ResolutionContext.
 */
trait ResolvesPublicContent
{
    /**
     * @return array<int, array<string, mixed>> resolved payloads keyed by content-part id
     */
    protected function resolveContentParts(?Content $content): array
    {
        $parts = $content?->parts->filter(
            fn (ContentPart $part) => in_array($part->type, ContentPartResolver::resolvableTypes(), true)
        );

        if ($parts === null || $parts->isEmpty()) {
            return [];
        }

        $locale = app()->getLocale();

        // Keyed by the blocks' own settings, so editing a block needs no flush. The short TTL
        // bounds time-relative modes (`upcoming`, `latest`); source-model saves flush the tag.
        $version = md5(serialize($parts->map(fn (ContentPart $part) => [$part->id, $part->type, $part->options, $part->json_content])->values()->all()));

        return Cache::tags([ContentPartResolver::CACHE_TAG])->remember(
            "resolved_parts:{$this->tenant->id}:{$locale}:{$this->subdomain}:{$version}",
            600,
            fn () => app(ContentPartResolver::class)->resolveAll($parts, new ResolutionContext(
                tenant: $this->tenant,
                locale: $locale,
                subdomain: $this->subdomain,
            )),
        );
    }
}
