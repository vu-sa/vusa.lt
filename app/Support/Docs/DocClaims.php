<?php

namespace App\Support\Docs;

/**
 * What the documentation claims about itself.
 */
class DocClaims
{
    /**
     * @param  array<string, list<string>>  $claims  doc page => test files it claims
     * @param  list<string>  $unclaimedPages  pages with prose but no evidence and no declared area
     * @param  array<string, array{area: ?string, models: list<string>, reviewedAt: ?string, tests: list<string>}>  $meta  every scanned page's frontmatter facts
     */
    public function __construct(
        public readonly array $claims,
        public readonly array $unclaimedPages,
        public readonly array $meta = [],
    ) {}

    /**
     * Every test file claimed by any page.
     *
     * @return list<string>
     */
    public function claimedTestFiles(): array
    {
        $files = array_merge(...array_values($this->claims)) ?: [];

        return array_values(array_unique($files));
    }

    /**
     * Claims pointing at a test file that no longer exists — a page describing
     * behaviour whose evidence has moved or been deleted.
     *
     * @return array<string, list<string>>
     */
    public function danglingClaims(): array
    {
        $dangling = [];

        foreach ($this->claims as $page => $files) {
            foreach ($files as $file) {
                if (! is_file(base_path($file))) {
                    $dangling[$page][] = $file;
                }
            }
        }

        return $dangling;
    }

    /**
     * Pages that explicitly declare they document a feature area.
     *
     * @return list<string>
     */
    public function pagesForArea(string $slug): array
    {
        $pages = [];

        foreach ($this->meta as $page => $facts) {
            if ($facts['area'] === $slug) {
                $pages[] = $page;
            }
        }

        return $pages;
    }

    public function reviewedAt(string $page): ?string
    {
        return $this->meta[$page]['reviewedAt'] ?? null;
    }
}
