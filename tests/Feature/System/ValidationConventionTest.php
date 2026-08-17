<?php

/**
 * Validation Convention Tests
 *
 * Validation lives in Form Requests, not inline in controllers. Inline `$request->validate()`
 * was how this codebase ended up with rules that drifted from what the controller actually
 * persisted — fields written with no rule at all (QuickLink's `lang`/`icon`, Relationship's
 * `description`, Banner's `link_url`), and duplicated rule blocks that fell out of sync.
 *
 * A Form Request also gives the arch layer something to inspect: RouteAuthorizationCoverageTest
 * can read its authorize(), which it cannot do for an inline array.
 *
 * The exemption list is empty and should stay that way. If a new controller needs to be added
 * here, that is a review conversation, not a default.
 *
 * @see .ai/rules/controllers.md
 * @see tests/Feature/System/RouteAuthorizationCoverageTest.php — the sibling arch guard
 */

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Controller methods still allowed to validate inline. Each entry needs a justification.
 *
 * @var array<int, string>
 */
const INLINE_VALIDATION_EXEMPTIONS = [];

/**
 * Every controller file under app/Http/Controllers.
 *
 * @return array<int, SplFileInfo>
 */
function controllerFiles(): array
{
    $files = [];

    /** @var SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Http/Controllers'))) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file;
        }
    }

    return $files;
}

test('controllers do not validate inline', function (): void {
    $offenders = [];

    foreach (controllerFiles() as $file) {
        $contents = (string) file_get_contents($file->getPathname());
        $relative = Str::after($file->getPathname(), base_path().'/');

        if (in_array($relative, INLINE_VALIDATION_EXEMPTIONS, true)) {
            continue;
        }

        foreach (['$request->validate(', 'Validator::make('] as $needle) {
            if (str_contains($contents, $needle)) {
                $offenders[] = "{$relative} uses {$needle}";
            }
        }
    }

    sort($offenders);

    expect($offenders)->toBe([]);
});

test('index listings read the page size through BaseIndexRequest', function (): void {
    $offenders = [];

    foreach (controllerFiles() as $file) {
        $contents = (string) file_get_contents($file->getPathname());

        if (str_contains($contents, "input('per_page'")) {
            $offenders[] = Str::after($file->getPathname(), base_path().'/');
        }
    }

    sort($offenders);

    // BaseIndexRequest::getPerPage() reads through validated(), so the max:100 cap always
    // applies; $request->input() bypasses it and re-hardcodes the default at every call site.
    expect($offenders)->toBe([]);
});

test('every Form Request declares rules', function (): void {
    $withoutRules = [];

    /** @var SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Http/Requests'))) as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $class = 'App\\Http\\Requests\\'.str_replace(
            '/',
            '\\',
            Str::of($file->getPathname())->after(app_path('Http/Requests').'/')->beforeLast('.php')->toString()
        );

        if (! class_exists($class) || ! is_subclass_of($class, FormRequest::class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract()) {
            continue;
        }

        if (! $reflection->hasMethod('rules')) {
            $withoutRules[] = $class;
        }
    }

    sort($withoutRules);

    expect($withoutRules)->toBe([]);
});
