<?php

namespace App\Support\Docs;

/**
 * Whether a documentation page has kept up with the tests it cites.
 */
class DocFreshness
{
    /**
     * @param  string  $page  repo-relative doc path
     * @param  string|null  $reviewedAt  the page's `last_reviewed` date, or null if it never set one
     * @param  string|null  $lastChangeAt  newest commit date across the cited tests (ISO 8601)
     * @param  list<string>  $changedSince  cited test files committed after the review date
     */
    public function __construct(
        public readonly string $page,
        public readonly ?string $reviewedAt,
        public readonly ?string $lastChangeAt,
        public readonly array $changedSince,
    ) {}

    public function neverReviewed(): bool
    {
        return $this->reviewedAt === null;
    }

    /**
     * The behaviour changed after a human last confirmed the prose — the page is
     * probably describing the old world.
     */
    public function hasDrifted(): bool
    {
        return $this->changedSince !== [];
    }
}
