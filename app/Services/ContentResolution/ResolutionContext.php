<?php

namespace App\Services\ContentResolution;

use App\Models\Tenant;

/**
 * Everything a resolver needs about *where* it's rendering that isn't in the
 * content part itself: which tenant's page this is, which locale to resolve
 * against, and the subdomain to build public URLs with.
 */
final readonly class ResolutionContext
{
    public function __construct(
        public Tenant $tenant,
        public string $locale,
        public string $subdomain,
        /** True for the admin preview endpoint — resolvers don't currently branch on this, but it's here for a future need (e.g. relaxed limits) rather than adding it as a breaking change later. */
        public bool $isPreview = false,
    ) {}
}
