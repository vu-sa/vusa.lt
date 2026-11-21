<?php

/**
 * A rule loads only for files its globs match, so a glob left behind by a move or rename turns the
 * rule off without an error. Every glob in a rule's frontmatter and in `.ai/rules/index.md` must
 * still match a file.
 *
 * @see .ai/rules/backend-layout.md "Move on touch"
 */

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

const RULE_GLOB_SKIPPED_DIRECTORIES = ['.git', '.worktrees', 'node_modules', 'vendor', 'storage', 'public/build'];

/**
 * `a/{b,c}.php` becomes `a/b.php` and `a/c.php`, so a stale alternative cannot hide behind a live one.
 *
 * @return array<int, string>
 */
function ruleGlobAlternatives(string $glob): array
{
    if (preg_match('/\{([^{}]*)\}/', $glob, $match, PREG_OFFSET_CAPTURE) !== 1) {
        return [$glob];
    }

    [$whole, $offset] = $match[0];

    return collect(explode(',', $match[1][0]))
        ->flatMap(fn (string $option): array => ruleGlobAlternatives(substr_replace($glob, $option, $offset, strlen($whole))))
        ->all();
}

function ruleGlobRegex(string $glob): string
{
    $tokens = ['**/' => '(.*/)?', '**' => '.*', '*' => '[^/]*', '?' => '[^/]'];
    $regex = '';

    for ($i = 0; $i < strlen($glob);) {
        foreach ($tokens as $token => $replacement) {
            if (substr($glob, $i, strlen($token)) === $token) {
                $regex .= $replacement;
                $i += strlen($token);

                continue 2;
            }
        }

        $regex .= preg_quote($glob[$i], '#');
        $i++;
    }

    return "#^{$regex}$#";
}

/**
 * @return array<int, string> repo-relative paths
 */
function ruleGlobRepoFiles(): array
{
    static $files;

    if ($files !== null) {
        return $files;
    }

    $finder = Finder::create()->files()->in(base_path())->ignoreDotFiles(false)->ignoreVCS(true)
        ->exclude(RULE_GLOB_SKIPPED_DIRECTORIES);

    $files = [];
    foreach ($finder as $file) {
        $files[] = $file->getRelativePathname();
    }

    return $files;
}

/**
 * @return array<string, array<int, string>> source => globs
 */
function ruleGlobSources(): array
{
    $sources = [];

    foreach (glob(base_path('.ai/rules/{,*/}*.md'), GLOB_BRACE) ?: [] as $path) {
        $relative = Str::after($path, base_path().'/');
        $contents = file_get_contents($path) ?: '';

        if (preg_match('/\A---\n(.*?)\n---\n/s', $contents, $frontmatter) === 1
            && preg_match('/^paths:\n((?:[ \t]+-[^\n]*\n?)+)/m', $frontmatter[1], $paths) === 1
            && preg_match_all("/^[ \t]*-[ \t]*['\"]?([^'\"\n]+?)['\"]?[ \t]*$/m", $paths[1], $globs) > 0) {
            $sources[$relative] = $globs[1];
        }
    }

    preg_match_all('/^\| (.+?) \| (\.ai\/rules\/[\w.\/-]+\.md) \|$/m', file_get_contents(base_path('.ai/rules/index.md')) ?: '', $rows, PREG_SET_ORDER);
    foreach ($rows as $index => [, $globs, $rule]) {
        $sources[".ai/rules/index.md#{$index} → {$rule}"] = preg_split('/\s*,\s*(?![^{]*\})/', trim($globs)) ?: [];
    }

    return $sources;
}

test('every rule glob still matches a file', function (): void {
    $files = ruleGlobRepoFiles();
    $stale = [];

    foreach (ruleGlobSources() as $source => $globs) {
        foreach (collect($globs)->flatMap(ruleGlobAlternatives(...)) as $glob) {
            $regex = ruleGlobRegex($glob);

            if (! collect($files)->contains(fn (string $file): bool => preg_match($regex, $file) === 1)) {
                $stale[] = "{$source}: {$glob}";
            }
        }
    }

    expect($stale)->toBeEmpty("These rule globs match no file, so the rule never loads for what they meant:\n".implode("\n", $stale));
});

test('the rules index has one row per scoped rule, with the same globs', function (): void {
    $sources = ruleGlobSources();
    $rows = collect($sources)->filter(fn (array $globs, string $source): bool => str_starts_with($source, '.ai/rules/index.md#'))
        ->mapToGroups(fn (array $globs, string $source): array => [Str::after($source, ' → ') => $globs]);
    $rules = collect($sources)->reject(fn (array $globs, string $source): bool => str_starts_with($source, '.ai/rules/index.md'));
    $sorted = fn (array $globs): array => collect($globs)->sort()->values()->all();
    $drift = [];

    foreach ($rules as $rule => $globs) {
        $ruleRows = $rows->get($rule, collect());

        if ($ruleRows->count() !== 1) {
            $drift[] = "{$rule}: {$ruleRows->count()} index rows";
        } elseif ($sorted($ruleRows->first()) !== $sorted($globs)) {
            $drift[] = "{$rule}: index row and frontmatter disagree";
        }
    }

    foreach ($rows->keys()->diff($rules->keys()) as $orphan) {
        $drift[] = "{$orphan}: indexed, but has no paths frontmatter";
    }

    expect($drift)->toBeEmpty(implode("\n", $drift));
});
