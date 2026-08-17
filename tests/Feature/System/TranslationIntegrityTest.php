<?php

/**
 * Translation Integrity Tests
 *
 * Two classes of i18n bug are invisible until a user hits them, because Laravel's fallback for a
 * missing translation is to render the key itself:
 *
 *  1. A controller references a short key that was never added to the lang files, so admins see
 *     the literal string "messages.meeting.updated".
 *  2. The lt/en pairs drift apart, so whichever locale is missing the key renders the raw key
 *     instead of a sentence.
 *
 * Both were present in the codebase. These tests walk the real lang files rather than a hand-kept
 * list, so a newly added key is covered the moment it exists.
 *
 * @see lang/readme.md — directory layout and the admin/public/shared split
 */

/**
 * Short keys built at runtime from a variable suffix, so the literal prefix in the source can
 * never resolve on its own. Each one has to be justified in review.
 */
const DYNAMIC_TRANSLATION_KEY_PREFIXES = [
    // App\Enums\InstitutionActivityStatus — the case name is appended at call time.
    'visak.activity.activity_status.',
];

/**
 * Directories holding the `lt`/`en` pairs of PHP short-key files.
 */
const LANG_AREAS = ['admin', 'shared', 'public'];

/**
 * Every short key referenced through __(), trans() or trans_choice() in app/.
 *
 * @return array<int, string>
 */
function referencedTranslationKeys(): array
{
    $keys = [];

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path()));

    foreach ($files as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        preg_match_all(
            "/(?:__|trans|trans_choice)\(\s*'([a-z][a-zA-Z0-9_]*\.[a-zA-Z0-9_.]+)'/",
            (string) file_get_contents($file->getPathname()),
            $matches
        );

        foreach ($matches[1] as $key) {
            $keys[] = $key;
        }
    }

    return array_values(array_unique($keys));
}

/**
 * Flatten a nested lang array into dot-notation keys.
 *
 * @param  array<mixed>  $lines
 * @return array<int, string>
 */
function flattenLangKeys(array $lines, string $prefix = ''): array
{
    $flat = [];

    foreach ($lines as $key => $value) {
        $dotted = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

        if (is_array($value)) {
            $flat = array_merge($flat, flattenLangKeys($value, $dotted));

            continue;
        }

        $flat[] = $dotted;
    }

    return $flat;
}

/**
 * Whether a dot-notation short key resolves in any lang area for the given locale.
 */
function translationKeyResolves(string $key, string $locale): bool
{
    $segments = explode('.', $key);
    $file = array_shift($segments);

    foreach (LANG_AREAS as $area) {
        $path = lang_path("{$area}/{$locale}/{$file}.php");

        if (! file_exists($path)) {
            continue;
        }

        $cursor = require $path;

        foreach ($segments as $segment) {
            if (! is_array($cursor) || ! array_key_exists($segment, $cursor)) {
                continue 2;
            }

            $cursor = $cursor[$segment];
        }

        return true;
    }

    return false;
}

test('every translation key referenced in app/ resolves in both locales', function (string $locale): void {
    $unresolved = collect(referencedTranslationKeys())
        ->reject(fn (string $key) => collect(DYNAMIC_TRANSLATION_KEY_PREFIXES)
            ->contains(fn (string $prefix) => str_starts_with($key, $prefix)))
        ->reject(fn (string $key) => translationKeyResolves($key, $locale))
        ->values()
        ->all();

    expect($unresolved)->toBe([]);
})->with(['lt', 'en']);

test('lt and en short-key files hold the same keys', function (): void {
    $drift = [];

    foreach (LANG_AREAS as $area) {
        foreach (glob(lang_path("{$area}/lt/*.php")) ?: [] as $ltPath) {
            $enPath = str_replace("/{$area}/lt/", "/{$area}/en/", $ltPath);
            $name = "{$area}/".basename($ltPath);

            if (! file_exists($enPath)) {
                $drift[] = "{$name}: missing the en counterpart";

                continue;
            }

            $ltLines = require $ltPath;
            $enLines = require $enPath;

            // A lang file that returns nothing makes every key in it silently unresolvable.
            if (! is_array($ltLines) || ! is_array($enLines)) {
                $drift[] = "{$name}: does not return an array";

                continue;
            }

            $ltKeys = flattenLangKeys($ltLines);
            $enKeys = flattenLangKeys($enLines);

            foreach (array_diff($ltKeys, $enKeys) as $key) {
                $drift[] = "{$name}: '{$key}' missing in en";
            }

            foreach (array_diff($enKeys, $ltKeys) as $key) {
                $drift[] = "{$name}: '{$key}' missing in lt";
            }
        }
    }

    expect($drift)->toBe([]);
});

test('lithuanian validation messages are actually translated', function (): void {
    $lt = require lang_path('shared/lt/validation.php');
    $en = require lang_path('shared/en/validation.php');

    // The `custom` block is placeholder scaffolding shipped by Laravel in both locales, and
    // `attributes` is intentionally locale-specific rather than a rule message.
    unset($lt['custom'], $lt['attributes'], $en['custom'], $en['attributes']);

    $ltFlat = flattenLangKeys($lt);

    $untranslated = collect($ltFlat)
        ->filter(function (string $key) use ($lt, $en): bool {
            $ltValue = data_get($lt, $key);
            $enValue = data_get($en, $key);

            return is_string($ltValue) && $ltValue === $enValue;
        })
        ->values()
        ->all();

    expect($untranslated)->toBe([]);
});
