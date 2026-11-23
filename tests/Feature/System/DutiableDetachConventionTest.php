<?php

/**
 * Dutiable integrity convention tests
 *
 * `BelongsToMany::detach()` writes through `newPivotQuery()->delete()` — a raw
 * query-builder delete that fires no model events, even with a custom pivot
 * class. Dutiable's `DutiableChanged` event is the only thing that resets the
 * permission cache (`HandleDutiableChange`) and cascades ex-officio rows
 * (`Dutiable::booted()`), so any detach on a relation backed by the `dutiables`
 * table silently skips both, leaving stale permissions and orphaned derived
 * rows behind.
 *
 * Delete dutiable rows through the model layer instead
 * (`$user->dutiables()->get()->each->delete()`), like UserController::forceDelete.
 *
 * @see https://github.com/JustasKav/vusa.lt/issues/623
 */

use Illuminate\Support\Str;

/**
 * Files still allowed to call detach() on a `duties`/`users` relation. Each entry
 * needs a justification.
 *
 * @var array<int, string>
 */
const DETACH_EXEMPTIONS = [
    // Role::duties() is Spatie's morphedByMany over model_has_roles, not the
    // dutiables pivot; its cache invalidation is handled explicitly below the call.
    'app/Observers/RoleTypeObserver.php',
    // Reservation::users() is a plain belongsToMany over reservation_user, not
    // the dutiables pivot.
    'app/Models/Reservation.php',
    // Task::users() is a plain belongsToMany over task_user, not the dutiables
    // pivot. The detach is required: task_user.task_id is a RESTRICT foreign key.
    'app/Models/Task.php',
];

const DUTIABLE_ATTACH_EXEMPTIONS = [
    // Reservation::users() writes to reservation_user.
    'app/Http/Controllers/Admin/ReservationController.php',
];

test('app code never detaches through a relation backed by the dutiables pivot', function (): void {
    $offenders = dutiableRelationCallOffenders('/\b(?:duties|users)\s*\(\s*\)\s*->\s*detach\s*\(/', DETACH_EXEMPTIONS);

    expect($offenders)->toBeEmpty();
});

test('app code avoids creating manual duty terms through pivot attaches', function (): void {
    $offenders = dutiableRelationCallOffenders(
        '/\bduties\s*\(\s*\)\s*->\s*attach\s*\(|\battachAudited\s*\(\s*([\'"])(?:users|duties)\1/',
        DUTIABLE_ATTACH_EXEMPTIONS,
    );

    expect($offenders)->toBeEmpty();
});

/**
 * @param  list<string>  $exemptions
 * @return list<string>
 */
function dutiableRelationCallOffenders(string $pattern, array $exemptions): array
{
    $offenders = [];

    /** @var SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())) as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $relative = Str::after($file->getPathname(), base_path().'/');

        if (in_array($relative, $exemptions, true)) {
            continue;
        }

        // Strip comments so references to forbidden calls do not trigger violations.
        $contents = (string) php_strip_whitespace($file->getPathname());

        preg_match_all($pattern, $contents, $matches);
        foreach (array_unique($matches[0]) as $call) {
            $offenders[] = "{$relative} uses {$call}";
        }
    }

    sort($offenders);

    return $offenders;
}
