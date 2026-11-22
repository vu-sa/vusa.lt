<?php

/**
 * Rule docs are read before every edit, so a reference to code that no longer exists misleads
 * quietly. References in inline code and rule frontmatter must still resolve.
 *
 * @see AGENTS.md "Comments" — the rule-docs paragraph
 */

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Glob;
use Symfony\Component\Yaml\Yaml;

/** Roots a relative path in a rule doc may be written against, besides the doc's own directory. */
const RULE_REFERENCE_PATH_ROOTS = ['', 'resources/js', 'resources/js/Components', 'resources/css', 'tests/Feature', 'docs'];

/**
 * @return array<string, string> repo-relative path => contents, without fences or ignored lines
 */
function ruleReferenceDocs(): array
{
    $paths = [base_path('AGENTS.md'), base_path('GEMINI.md'), ...glob(base_path('.ai/rules/{,*/}*.md'), GLOB_BRACE) ?: []];

    foreach (['app', 'resources', 'tests', '.storybook'] as $dir) {
        foreach (Finder::create()->files()->in(base_path($dir))->name('CLAUDE.md')->exclude('node_modules') as $file) {
            $paths[] = $file->getPathname();
        }
    }

    return collect($paths)
        ->mapWithKeys(fn (string $path): array => [Str::after($path, base_path().'/') => $path])
        ->map(function (string $path): string {
            $markdown = preg_replace('/^```.*?^```/ms', '', file_get_contents($path) ?: '');

            return preg_replace('/^[^\r\n]*<!-- ref-ignore -->[^\r\n]*/m', '', $markdown);
        })
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

test('every scoped rule paths glob matches a file', function (): void {
    $files = [];
    $finder = Finder::create()->files()->in(base_path())->ignoreDotFiles(false)
        ->exclude(['vendor', 'node_modules', 'storage', 'public/build', '.git', '.worktrees']);

    foreach ($finder as $file) {
        $files[] = $file->getRelativePathname();
    }

    $missing = [];

    foreach (ruleReferenceDocs() as $doc => $markdown) {
        if (dirname($doc) !== '.ai/rules' || ! preg_match('/\A---\R(.*?)\R---(?:\R|\z)/s', $markdown, $frontmatter)) {
            continue;
        }

        foreach (Yaml::parse($frontmatter[1])['paths'] ?? [] as $glob) {
            if (preg_grep(Glob::toRegex($glob, strictLeadingDot: false), $files) === []) {
                $missing[] = "{$doc}: {$glob}";
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBeEmpty(implode("\n", array_unique($missing)));
});

test('every Composer and npm script named in the rule docs exists', function (): void {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
    $package = json_decode(file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);
    $composerCommands = ['install', 'update', 'require', 'remove', 'show', 'dump-autoload', 'run-script', ...array_keys($composer['scripts'])];
    $npmScripts = array_keys($package['scripts']);
    $missing = [];

    foreach (ruleReferenceDocs() as $doc => $markdown) {
        foreach (ruleReferenceCodeSpans($markdown) as $span) {
            preg_match_all('/(?<![\w\/-])(composer|npm\h+run)\h+([\w][\w:.-]*)/', $span, $matches, PREG_SET_ORDER);

            foreach ($matches as [, $runner, $script]) {
                $allowed = $runner === 'composer' ? $composerCommands : $npmScripts;

                if (! in_array($script, $allowed, true)) {
                    $missing[] = "{$doc}: {$runner} {$script}";
                }
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBeEmpty(implode("\n", array_unique($missing)));
});

test('every frontend alias named in the rule docs resolves to a module', function (): void {
    $root = realpath(resource_path('js'));
    $missing = [];

    foreach (ruleReferenceDocs() as $doc => $markdown) {
        foreach (ruleReferenceCodeSpans($markdown) as $span) {
            preg_match_all('~@/([\w./*<>…-]+)~u', $span, $matches, PREG_SET_ORDER);

            foreach ($matches as [$specifier, $path]) {
                if (strpbrk($path, '*<') !== false || str_contains($path, '…')) {
                    continue;
                }

                $exists = collect(['', '.ts', '.d.ts', '.vue', '.js', '/index.ts'])
                    ->contains(function (string $suffix) use ($root, $path): bool {
                        $resolved = realpath("{$root}/{$path}{$suffix}");

                        return $resolved !== false && str_starts_with($resolved, $root.'/') && is_file($resolved);
                    });

                if (! $exists) {
                    $missing[] = "{$doc}: {$specifier}";
                }
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBeEmpty(implode("\n", array_unique($missing)));
});
