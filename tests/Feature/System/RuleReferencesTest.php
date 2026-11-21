<?php

/**
 * Rule docs are read before every edit, so a reference to code that no longer exists misleads
 * quietly. Every app `Class::member` and file path they name in inline code must still resolve.
 *
 * @see AGENTS.md "Comments" — the rule-docs paragraph
 */

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

/** Roots a relative path in a rule doc may be written against, besides the doc's own directory. */
const RULE_REFERENCE_PATH_ROOTS = ['', 'resources/js', 'resources/js/Components', 'resources/css', 'tests/Feature', 'docs'];

/**
 * @return array<string, string> repo-relative path => contents, fenced code blocks stripped
 */
function ruleReferenceDocs(): array
{
    $paths = ['AGENTS.md', ...glob(base_path('.ai/rules/{,*/}*.md'), GLOB_BRACE) ?: []];

    foreach (['app', 'resources', 'tests', '.storybook'] as $dir) {
        foreach (Finder::create()->files()->in(base_path($dir))->name('CLAUDE.md')->exclude('node_modules') as $file) {
            $paths[] = $file->getPathname();
        }
    }

    return collect($paths)
        ->mapWithKeys(fn (string $path): array => [Str::after($path, base_path().'/') => $path])
        ->map(fn (string $path): string => preg_replace('/^```.*?^```/ms', '', file_get_contents($path) ?: ''))
        ->all();
}

/**
 * @return array<int, string>
 */
function ruleReferenceCodeSpans(string $markdown): array
{
    preg_match_all('/`([^`\n]+)`/', $markdown, $matches);

    return $matches[1];
}

/**
 * @return array<string, array<int, class-string>> short class name => App classes with it
 */
function ruleReferenceAppClasses(): array
{
    $classes = [];

    foreach (Finder::create()->files()->in(app_path())->name('*.php') as $file) {
        $class = 'App\\'.Str::of($file->getRelativePathname())->beforeLast('.php')->replace('/', '\\');
        $classes[class_basename($class)][] = $class;
    }

    return $classes;
}

function ruleReferenceMemberExists(string $class, string $member): bool
{
    if (! (class_exists($class) || interface_exists($class) || trait_exists($class))) {
        return false;
    }

    if ($member === 'class') {
        return true;
    }

    $name = ltrim($member, '$');
    $reflection = new ReflectionClass($class);

    return method_exists($class, $name)
        || method_exists($class, 'scope'.ucfirst($name))
        || $reflection->hasConstant($name)
        || property_exists($class, $name)
        // Static calls a model forwards to its query builder (`User::where()`).
        || (is_subclass_of($class, Model::class) && (method_exists(EloquentBuilder::class, $name) || method_exists(QueryBuilder::class, $name)))
        // A model column or accessor, as listed by ide-helper.
        || preg_match('/@property(-read|-write)?\s+\S+\s+\$'.preg_quote($name, '/').'\b/', (string) $reflection->getDocComment()) === 1;
}

test('every app Class::member named in the rule docs exists', function (): void {
    $appClasses = ruleReferenceAppClasses();
    $missing = [];

    foreach (ruleReferenceDocs() as $doc => $markdown) {
        foreach (ruleReferenceCodeSpans($markdown) as $span) {
            preg_match_all('/(?<![\w\\\\$*])([A-Z]\w*)::(\$?[A-Za-z_]\w*)/', $span, $matches, PREG_SET_ORDER);

            foreach ($matches as [, $short, $member]) {
                $candidates = $appClasses[$short] ?? [];

                if ($candidates === []) {
                    continue; // Framework, vendor or placeholder class.
                }

                if (! collect($candidates)->contains(fn (string $class): bool => ruleReferenceMemberExists($class, $member))) {
                    $missing[] = "{$doc}: {$short}::{$member}";
                }
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBeEmpty(implode("\n", array_unique($missing)));
});

test('every file path named in the rule docs exists', function (): void {
    $missing = [];

    foreach (ruleReferenceDocs() as $doc => $markdown) {
        $roots = [...RULE_REFERENCE_PATH_ROOTS, dirname($doc)];

        foreach (ruleReferenceCodeSpans($markdown) as $span) {
            $path = preg_replace('/(:\d+(-\d+)?|#[\w-]+)$/', '', $span);

            if (! preg_match('#^[\w.-]+(/[\w.-]+)*/[\w.-]+\.(php|ts|vue|md|json|js|mts|css|yml)$#', $path)) {
                continue; // Not a plain path: globs, placeholders, prose.
            }

            $exists = collect($roots)->contains(fn (string $root): bool => file_exists(base_path(ltrim("{$root}/{$path}", '/'))));

            if (! $exists) {
                $missing[] = "{$doc}: {$path}";
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBeEmpty(implode("\n", array_unique($missing)));
});
