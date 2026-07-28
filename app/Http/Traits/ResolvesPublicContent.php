<?php

namespace App\Http\Traits;

use App\Models\Content;
use App\Services\ContentResolution\ContentPartResolver;
use App\Services\ContentResolution\ResolutionContext;

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
        if (! $content) {
            return [];
        }

        return app(ContentPartResolver::class)->resolveAll(
            $content->parts,
            new ResolutionContext(
                tenant: $this->tenant,
                locale: app()->getLocale(),
                subdomain: $this->subdomain,
            ),
        );
    }
}
