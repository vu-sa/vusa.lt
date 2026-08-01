<?php

/**
 * `ServerDataTable` resolves its empty-state heading through
 * `entities.{entityName}.model`, so a missing key leaks the raw key string into the
 * admin UI ("entities.news.model") instead of a model name. These keys also feed
 * breadcrumb and sidebar helpers, so a gap is never merely cosmetic.
 *
 * The scan runs while Pest collects the file, before the application is booted, so it
 * uses plain filesystem calls rather than the `base_path()` / `File` helpers.
 */
$projectRoot = dirname(__DIR__, 3);

$entityNames = collect(glob($projectRoot.'/resources/js/Pages/Admin/*/Index*.vue') ?: [])
    ->map(function (string $path): ?string {
        $source = (string) file_get_contents($path);

        // Pages declare the key they will look up as a top-level constant; those that
        // declare none do not render a ServerDataTable empty state.
        if (preg_match('/^const entityName\s*=\s*\'([^\']+)\'/m', $source, $matches) === 1) {
            return $matches[1];
        }

        return null;
    })
    ->filter()
    ->unique()
    ->sort()
    ->values()
    ->all();

dataset('admin index entity names', array_map(
    fn (string $entityName): array => [$entityName],
    $entityNames,
));

test('the entity name scan finds every admin index page', function () use ($entityNames): void {
    // Guards the regex above: a rename that silently matches nothing would make
    // every dataset case vanish and the suite pass while covering nothing.
    expect($entityNames)->toHaveCount(24);
});

test('every admin index entity name has a Lithuanian model translation', function (string $entityName): void {
    $translations = require base_path('lang/admin/lt/entities.php');

    expect($translations)->toHaveKey($entityName)
        ->and($translations[$entityName])->toHaveKey('model')
        ->and($translations[$entityName]['model'])->not->toBe('');
})->with('admin index entity names');

test('every admin index entity name has an English model translation', function (string $entityName): void {
    $translations = require base_path('lang/admin/en/entities.php');

    expect($translations)->toHaveKey($entityName)
        ->and($translations[$entityName])->toHaveKey('model')
        ->and($translations[$entityName]['model'])->not->toBe('');
})->with('admin index entity names');

test('Lithuanian model translations declare the genitive plural form used by empty states', function (string $entityName): void {
    $translations = require base_path('lang/admin/lt/entities.php');

    // "Nėra :models" needs the [10,*] form; a range capped below the wildcard leaves
    // larger counts unmatched and renders the raw pluralization string.
    expect($translations[$entityName]['model'])->toContain('[10,*]');
})->with('admin index entity names');
