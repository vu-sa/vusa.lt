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

const RULE_GLOB_SEARCH_ROOTS = ['app', 'bootstrap', 'config', 'database', 'deployment', 'docs', 'lang', 'public', 'resources', 'routes', 'tests', '.github'];

function ruleGlobRegex(string $glob): string
{
    $tokens = ['**/' => '(.*/)?', '**' => '.*', '*' => '[^/]*', '?' => '[^/]', '{' => '(', '}' => ')', ',' => '|'];
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

    $roots = array_values(array_filter(array_map(base_path(...), RULE_GLOB_SEARCH_ROOTS), is_dir(...)));
    $finder = Finder::create()->files()->in($roots)->exclude(['node_modules', 'build', 'vendor'])->ignoreDotFiles(false);

    $files = ['CLAUDE.md', 'AGENTS.md'];
    foreach ($finder as $file) {
        $files[] = Str::after($file->getPathname(), base_path().'/');
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

        if (preg_match('/\A---\n(.*?)\n---/s', $contents, $frontmatter) === 1
            && preg_match_all("/^\s*-\s*'?([^'\n]+?)'?\s*$/m", $frontmatter[1], $globs) > 0) {
            $sources[$relative] = $globs[1];
        }
    }

    preg_match_all('/^\| (.+?) \| (\.ai\/rules\/[\w.\/-]+\.md) \|$/m', file_get_contents(base_path('.ai/rules/index.md')) ?: '', $rows, PREG_SET_ORDER);
    foreach ($rows as [, $globs, $rule]) {
        $sources[".ai/rules/index.md → {$rule}"] = preg_split('/\s*,\s*(?![^{]*\})/', trim($globs)) ?: [];
    }

    return $sources;
}

test('every rule glob still matches a file', function (): void {
    $files = ruleGlobRepoFiles();
    $stale = [];

    foreach (ruleGlobSources() as $source => $globs) {
        foreach ($globs as $glob) {
            $regex = ruleGlobRegex($glob);

            if (! collect($files)->contains(fn (string $file): bool => preg_match($regex, $file) === 1)) {
                $stale[] = "{$source}: {$glob}";
            }
        }
    }

    expect($stale)->toBeEmpty("These rule globs match no file, so the rule never loads for what they meant:\n".implode("\n", $stale));
});

test('the rules index lists the same globs as each rule', function (): void {
    $sources = ruleGlobSources();
    $drift = [];

    foreach ($sources as $source => $globs) {
        if (! str_starts_with($source, '.ai/rules/index.md → ')) {
            continue;
        }

        $rule = Str::after($source, ' → ');
        $declared = $sources[$rule] ?? [];

        if (collect($globs)->sort()->values()->all() !== collect($declared)->sort()->values()->all()) {
            $drift[] = $rule;
        }
    }

    expect($drift)->toBeEmpty("The index row and the frontmatter disagree for:\n".implode("\n", $drift));
});
