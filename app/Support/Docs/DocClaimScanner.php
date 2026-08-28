<?php

namespace App\Support\Docs;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads what documentation pages declare about themselves: the feature area they
 * cover, the models they concern, when a human last reviewed them, and the test
 * files that prove their prose.
 *
 * A `tests:` claim says "the behaviour described here is proven by these files".
 * It is written by hand, by the person writing the prose, in the same file and
 * the same commit — the only shape of rule that survives here, given that 5 of
 * the last 291 commits touched a non-changelog doc page.
 *
 * Claims name test *files*, never individual it() names: test names churn
 * constantly and a rename would break the docs for no informational gain,
 * whereas a deleted or moved file is exactly what you want to hear about.
 */
class DocClaimScanner
{
    /**
     * Only root-locale pages are read. `docs/en/**` is a translation, not an
     * independent claim — reading both would duplicate every claim and guarantee
     * drift. `maintainers` holds the generated coverage dashboard, not prose.
     * Matched against the top-level directory, never as a substring.
     *
     * @var list<string>
     */
    private const array EXCLUDED = ['en', '_parts', '.vitepress', 'public', 'maintainers'];

    /** @var list<string> */
    private array $warnings = [];

    public function scan(string $docsDirectory): DocClaims
    {
        $claims = [];
        $unclaimedPages = [];
        $meta = [];

        $finder = Finder::create()->files()->in($docsDirectory)->name('*.md');

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            if ($this->isExcluded($file->getRelativePath())) {
                continue;
            }

            $relative = str_replace(base_path().'/', '', $file->getPathname());
            $facts = $this->parseFrontmatter((string) file_get_contents($file->getPathname()), $relative);

            // `coverage: ignore` opts a handbook page out entirely — no claim, no
            // area, and no nagging on the "no evidence cited" list.
            if ($facts['ignore']) {
                continue;
            }

            $meta[$relative] = ['area' => $facts['area'], 'models' => $facts['models'], 'reviewedAt' => $facts['reviewedAt'], 'tests' => $facts['tests']];

            if ($facts['tests'] !== []) {
                $claims[$relative] = $facts['tests'];
            }

            if ($facts['tests'] === [] && $facts['area'] === null) {
                $unclaimedPages[] = $relative;
            }
        }

        ksort($claims);
        ksort($meta);
        sort($unclaimedPages);

        return new DocClaims($claims, $unclaimedPages, $meta);
    }

    /**
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * The frontmatter facts a page declares. Parsed with a real YAML reader
     * (already installed) rather than by hand: the block can now hold nested
     * lists, inline arrays and quoted dates, and the artisan command — unlike the
     * PHP-less docs build — can afford the dependency.
     *
     * @return array{area: ?string, models: list<string>, reviewedAt: ?string, tests: list<string>, ignore: bool}
     */
    private function parseFrontmatter(string $contents, string $page): array
    {
        $empty = ['area' => null, 'models' => [], 'reviewedAt' => null, 'tests' => [], 'ignore' => false];

        if (! preg_match('/\A---\R(.*?)\R---\R/s', $contents, $frontmatter)) {
            return $empty;
        }

        try {
            $parsed = Yaml::parse($frontmatter[1]);
        } catch (\Throwable $e) {
            $this->warnings[] = "{$page}: unreadable frontmatter ({$e->getMessage()})";

            return $empty;
        }

        if (! is_array($parsed)) {
            return $empty;
        }

        return [
            'area' => $this->stringOrNull($parsed['area'] ?? null),
            'models' => $this->stringList($parsed['models'] ?? []),
            'reviewedAt' => $this->dateString($parsed['last_reviewed'] ?? null),
            'tests' => $this->validTestPaths($this->stringList($parsed['tests'] ?? []), $page),
            'ignore' => ($parsed['coverage'] ?? null) === 'ignore',
        ];
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    private function validTestPaths(array $paths, string $page): array
    {
        $valid = [];

        foreach ($paths as $path) {
            if (! str_starts_with($path, 'tests/')) {
                $this->warnings[] = "{$page}: cited path is not under tests/ — {$path}";

                continue;
            }

            $valid[] = $path;
        }

        return $valid;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * A `YYYY-MM-DD` date regardless of how the author wrote it: YAML turns an
     * unquoted `2026-08-26` into a Unix timestamp, a quoted one stays a string,
     * and the PARSE_DATETIME flag would give a DateTime — normalise all three.
     */
    private function dateString(mixed $value): ?string
    {
        return match (true) {
            is_int($value) => date('Y-m-d', $value),
            $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
            is_string($value) && $value !== '' => substr($value, 0, 10),
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn ($v) => is_string($v) ? trim($v) : null, $value),
            fn ($v) => $v !== null && $v !== '',
        ));
    }

    private function isExcluded(string $relativePath): bool
    {
        if ($relativePath === '') {
            return false;
        }

        $top = explode('/', $relativePath)[0];

        return in_array($top, self::EXCLUDED, true);
    }
}
