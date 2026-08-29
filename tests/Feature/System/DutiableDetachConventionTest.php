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
];

test('app code never detaches through a relation backed by the dutiables pivot', function (): void {
    $offenders = [];

    /** @var SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())) as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $relative = Str::after($file->getPathname(), base_path().'/');

        if (in_array($relative, DETACH_EXEMPTIONS, true)) {
            continue;
        }

        // Strip comments: explanatory prose mentioning the forbidden call is
        // not an offence. php_strip_whitespace also drops whitespace, which the
        // needle (no spaces inside) does not depend on.
        $contents = (string) php_strip_whitespace($file->getPathname());

        // User::duties() and Duty::users() are the only two relations on the
        // dutiables pivot.
        foreach (['duties()->detach(', 'users()->detach('] as $needle) {
            if (str_contains($contents, $needle)) {
                $offenders[] = "{$relative} uses {$needle}";
            }
        }
    }

    sort($offenders);

    expect($offenders)->toBeEmpty();
});
