<?php

namespace App\Services\ContentResolution;

use App\Models\ContentPart;
use Illuminate\Support\Collection;

interface ResolvesContentPart
{
    /**
     * Resolve every block of one type on one page in a single pass — batching (shared
     * lookups, `whereIn` over pinned ids, …) is the resolver's responsibility, not the
     * caller's, since only the resolver knows which parts of its own options can be
     * queried together.
     *
     * @param  Collection<int, ContentPart>  $parts  keyed by content-part id
     * @return array<int, array<string, mixed>> resolved payload keyed by content-part id
     */
    public function resolve(Collection $parts, ResolutionContext $context): array;
}
