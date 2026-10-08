<?php

namespace App\Services\Media;

/**
 * Transitional(legacy-images): remove when media:backfill-legacy-images --status shows 0 pending.
 */
final readonly class LegacyImageSource
{
    public const string RESOLVED = 'resolved';

    public const string PLACEHOLDER = 'placeholder';

    public const string FOREIGN = 'foreign';

    public const string JUNK = 'junk';

    public const string MISSING = 'missing';

    public const string EMPTY = 'empty';

    public function __construct(
        public string $outcome,
        public ?string $path = null,
    ) {}

    public function isResolved(): bool
    {
        return $this->outcome === self::RESOLVED && $this->path !== null;
    }
}
