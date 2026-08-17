<?php

/**
 * The aliases in App\Support\MorphMap are literally the strings sitting in every polymorphic
 * column, so a rename or a missing entry silently orphans rows.
 */

use App\Models\Meeting;
use App\Models\Pivots\ReservationResource;
use App\Models\PublicInstitution;
use App\Models\PublicMeeting;
use App\Models\PublicNews;
use App\Models\PublicPage;
use App\Support\Auditables;
use App\Support\Commentables;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

/**
 * Model classes that intentionally share another model's alias rather than owning one.
 */
function morphMapExemptClasses(): array
{
    return [
        // Abstract-ish base class every app model extends.
        App\Models\Model::class,
        ...array_keys(MorphMap::ALIASED_TO_PARENT),
    ];
}

/** `*_type` columns that describe a file format or a behaviour, not a model. */
const NON_POLYMORPHIC_TYPE_COLUMNS = ['action_type', 'content_type', 'file_type', 'mime_type'];

/** Migrations are excluded: the ones predating the map wrote class names legitimately. */
const MORPH_LITERAL_SCAN_PATHS = ['app', 'database/seeders', 'database/factories', 'tests'];

test('the map is registered', function (): void {
    expect(Relation::morphMap())->toMatchArray(MorphMap::MAP);
});

test('every model in app/Models has an alias', function (): void {
    $classes = collect(array_merge(
        glob(app_path('Models/*.php')) ?: [],
        glob(app_path('Models/Pivots/*.php')) ?: [],
    ))
        ->map(function (string $path): string {
            $relative = Str::of($path)->after(app_path().'/')->beforeLast('.php')->replace('/', '\\');

            return 'App\\'.$relative;
        })
        ->filter(fn (string $class): bool => class_exists($class) && is_subclass_of($class, Model::class))
        ->reject(fn (string $class): bool => in_array($class, morphMapExemptClasses(), true))
        ->values();

    $missing = $classes->reject(fn (string $class): bool => in_array($class, MorphMap::MAP, true));

    expect($missing->all())->toBe([]);
});

test('aliases are the snake_case class basename', function (): void {
    $wrong = [];

    foreach (MorphMap::MAP as $alias => $class) {
        $expected = Str::snake(class_basename($class));

        if ($alias !== $expected) {
            $wrong[] = "{$class}: '{$alias}' should be '{$expected}'";
        }
    }

    expect($wrong)->toBe([]);
});

test('getMorphClass returns the alias, including for the public mirrors', function (): void {
    expect((new Meeting)->getMorphClass())->toBe('meeting')
        ->and((new ReservationResource)->getMorphClass())->toBe('reservation_resource')
        ->and((new PublicNews)->getMorphClass())->toBe('news')
        ->and((new PublicPage)->getMorphClass())->toBe('page')
        ->and((new PublicInstitution)->getMorphClass())->toBe('institution')
        ->and((new PublicMeeting)->getMorphClass())->toBe('meeting');
});

test('the alias vocabularies that predate the map still resolve to mapped classes', function (): void {
    // Commentables and Auditables keep their own camelCase aliases because those are wire
    // format (API paths, broadcast channel names). What must not drift is the set of classes
    // behind them.
    $unmapped = collect([...Commentables::TYPES, ...Auditables::SUBJECT_TYPES])
        ->reject(fn (string $class): bool => in_array($class, MorphMap::MAP, true))
        ->values();

    expect($unmapped->all())->toBe([]);
});

test('no source file writes a class name into a polymorphic column', function (): void {
    // Code and its test can agree on `Foo::class` while the database holds the alias — the
    // suite passes and the query matches nothing in production.
    $pattern = '/([\'"])([a-z_]+_type)\1\s*(?:=>|,)\s*[\\\\A-Za-z_]+::class/';

    $offenders = [];

    foreach (MORPH_LITERAL_SCAN_PATHS as $path) {
        $directory = base_path($path);

        if (! is_dir($directory)) {
            continue;
        }

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            // The map is where the class names are supposed to live.
            if ($file->getRealPath() === app_path('Support/MorphMap.php')) {
                continue;
            }

            foreach (file($file->getRealPath()) ?: [] as $number => $line) {
                if (preg_match($pattern, $line, $matches) !== 1) {
                    continue;
                }

                if (in_array($matches[2], NON_POLYMORPHIC_TYPE_COLUMNS, true)) {
                    continue;
                }

                $relative = Str::after($file->getRealPath(), base_path().'/');
                $offenders[] = "{$relative}:".($number + 1).' — '.trim($line);
            }
        }
    }

    expect($offenders)->toBe([]);
});
