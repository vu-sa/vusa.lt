<?php

use App\Http\Controllers\AdminController;

/**
 * Admin index pages resolve model names through `entities.{entityName}.model`, so a
 * missing key leaks the raw key string into the admin UI ("entities.news.model") instead
 * of a model name. These keys also feed breadcrumb and sidebar helpers, so a gap is never
 * merely cosmetic.
 */
$projectRoot = dirname(__DIR__, 3);

$entityNames = [
    'agendaItem', 'banner', 'calendar', 'document', 'duty', 'dutyType', 'eventType', 'form', 'goal',
    'institution', 'institutionType', 'mailingList', 'meeting', 'news', 'page', 'permission', 'problem',
    'quickLink', 'reservation', 'resource', 'role', 'studyProgram', 'studySet', 'tag', 'task', 'tenant', 'user',
];

dataset('admin index entity names', array_map(
    fn (string $entityName): array => [$entityName],
    $entityNames,
));

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

$appFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($projectRoot.'/app', FilesystemIterator::SKIP_DOTS),
);

$entityMessageEntities = collect(iterator_to_array($appFiles))
    ->filter(fn (SplFileInfo $file): bool => $file->isFile() && $file->getExtension() === 'php')
    ->flatMap(function (SplFileInfo $file): array {
        $source = (string) file_get_contents($file->getPathname());

        preg_match_all('/->entityMessage\(\s*(?:\'[^\']+\'|\$[A-Za-z_]\w*)\s*,\s*\'([^\']+)\'\s*\)/', $source, $matches);

        return $matches[1];
    })
    ->unique()
    ->sort()
    ->values()
    ->all();

dataset('entityMessage entity names', array_map(
    fn (string $entityName): array => [$entityName],
    $entityMessageEntities,
));

test('the entityMessage scan finds entities across app/', function () use ($entityMessageEntities): void {
    expect($entityMessageEntities)->not->toBeEmpty()
        ->toContain('supportRequest');
});

test('every entityMessage entity has a model and valid gender in both locales', function (string $entityName, string $locale): void {
    $translations = require base_path("lang/admin/{$locale}/entities.php");

    expect($translations)->toHaveKey($entityName)
        ->and($translations[$entityName])->toHaveKeys(['model', 'gender'])
        ->and($translations[$entityName]['model'])->toBeString()->not->toBe('')
        ->and($translations[$entityName]['gender'])->toBeIn(['f', 'm']);
})->with('entityMessage entity names')->with(['lt', 'en']);

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
