<?php

use App\Http\Controllers\AdminController;

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
    expect($entityNames)->toHaveCount(22);
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

/**
 * Entities that are not real entities: they hold field labels or meta strings rather than a
 * model name, so they take no gender.
 */
const GENDERLESS_ENTITY_KEYS = ['meta', 'common', 'contentPart'];

test('every entity declares a gender the message files can resolve', function (string $locale): void {
    $entities = require base_path("lang/admin/{$locale}/entities.php");
    $messages = require base_path("lang/admin/{$locale}/messages.php");
    $forms = require base_path("lang/admin/{$locale}/forms.php");

    $problems = [];

    foreach ($entities as $entity => $lines) {
        if (in_array($entity, GENDERLESS_ENTITY_KEYS, true)) {
            continue;
        }

        $gender = $lines['gender'] ?? null;

        if (! in_array($gender, ['f', 'm'], true)) {
            $problems[] = "{$entity}: gender must be 'f' or 'm', got ".var_export($gender, true);

            continue;
        }

        // entityMessage() / newEntityTitle() build the key from the gender, so a variant
        // missing for one gender only surfaces on the entity that happens to use it.
        foreach (['created', 'updated', 'deleted', 'restored'] as $action) {
            if (! isset($messages[$action][$gender])) {
                $problems[] = "{$entity}: messages.{$action}.{$gender} is missing";
            }
        }

        foreach (['new_model', 'edit_model'] as $key) {
            if (! isset($forms[$key][$gender])) {
                $problems[] = "{$entity}: forms.{$key}.{$gender} is missing";
            }
        }
    }

    expect($problems)->toBeEmpty();
})->with(['lt', 'en']);

test('entityMessage renders the gendered Lithuanian participle', function (): void {
    app()->setLocale('lt');

    $controller = new class extends AdminController
    {
        public function message(string $action, string $entity): string
        {
            return $this->entityMessage($action, $entity);
        }
    };

    expect($controller->message('created', 'news'))->toBe('Naujiena sėkmingai sukurta.')
        ->and($controller->message('created', 'page'))->toBe('Puslapis sėkmingai sukurtas.')
        ->and($controller->message('restored', 'reservation'))->toBe('Rezervacija sėkmingai atkurta.');
});
