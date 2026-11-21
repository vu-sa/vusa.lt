<?php

/**
 * Actions and Services take the acting user as an argument and never a Request, so a command,
 * job or test can call them. Ratchet the remaining exceptions; each count may only fall.
 *
 * @see .ai/rules/backend-layout.md
 */

use Illuminate\Support\Str;

const BACKEND_LAYOUT_AMBIENT_ACTOR_NEEDLES = [
    'Auth::user(',
    'Auth::id(',
    'auth()->user(',
    'auth()->id(',
    'request()->user(',
];

const BACKEND_LAYOUT_REQUEST_NEEDLES = [
    'Illuminate\Http\Request',
    'Illuminate\Foundation\Http\FormRequest',
    'App\Http\Requests\\',
];

/** @var array<string, int> path => ambient actor reads still allowed */
const BACKEND_LAYOUT_AMBIENT_ACTOR_ALLOWLIST = [
    'app/Actions/GetAttachableTypesForDuty.php' => 1,
    'app/Actions/GetPublicEditLink.php' => 1,
    'app/Services/AtstovavimasDashboardService.php' => 2,
    'app/Services/InstitutionPresenter.php' => 1,
    // `$viewer ?? auth()->user()` fallbacks for callers that pass no viewer.
    'app/Services/KnowledgeBasePresenter.php' => 1,
    'app/Services/LearningPathPresenter.php' => 3,
    'app/Services/MeetingPanelPresenter.php' => 1,
    // `$user ?: Auth::user()` defaults on every public method.
    'app/Services/PermissionService.php' => 3,
    // Public-link audit names the user, or "system" in a job.
    'app/Services/Sharepoint/SharepointGraphService.php' => 1,
    'app/Services/TanstackTableService.php' => 2,
];

/** @var array<string, int> path => Request imports still allowed */
const BACKEND_LAYOUT_REQUEST_ALLOWLIST = [
    // Index builders read BaseIndexRequest's sorting and filter helpers.
    'app/Actions/ApplyReservationIndexFilters.php' => 1,
    'app/Actions/BuildCalendarIndexQuery.php' => 1,
    'app/Actions/BuildDutyIndexQuery.php' => 1,
    'app/Actions/BuildFormIndexQuery.php' => 1,
    'app/Actions/BuildMailQueuePage.php' => 1,
    'app/Actions/BuildProblemIndexQuery.php' => 1,
    'app/Actions/BuildSupportRequestCollection.php' => 1,
    'app/Actions/BuildTaskIndexQuery.php' => 1,
    'app/Actions/BuildUserIndexQuery.php' => 1,
    // Reads uploaded files from the request.
    'app/Actions/HandleModelMediaUploads.php' => 1,
];

/**
 * @param  array<int, string>  $directories
 * @param  array<int, string>  $needles
 * @param  array<string, int>  $allowlist
 * @return array<int, string>
 */
function backendLayoutOffenders(array $directories, array $needles, array $allowlist): array
{
    $counts = [];

    foreach ($directories as $directory) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($directory))) as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = php_strip_whitespace($file->getPathname());
            $count = array_sum(array_map(fn (string $needle): int => substr_count($contents, $needle), $needles));

            if ($count > 0) {
                $counts[Str::after($file->getPathname(), base_path().'/')] = $count;
            }
        }
    }

    $offenders = [];
    foreach (array_unique([...array_keys($counts), ...array_keys($allowlist)]) as $path) {
        $actual = $counts[$path] ?? 0;
        $allowed = $allowlist[$path] ?? 0;

        if ($actual !== $allowed) {
            $offenders[] = "{$path}: {$actual} (allowed {$allowed})".($actual < $allowed ? ' — lower the count' : '');
        }
    }

    sort($offenders);

    return $offenders;
}

test('actions and services do not read the acting user from ambient state', function (): void {
    $offenders = backendLayoutOffenders(['app/Actions', 'app/Services'], BACKEND_LAYOUT_AMBIENT_ACTOR_NEEDLES, BACKEND_LAYOUT_AMBIENT_ACTOR_ALLOWLIST);

    expect($offenders)->toBeEmpty("Take the User as an argument and read it in the controller or Form Request.\n".implode("\n", $offenders));
});

test('actions do not take a Request', function (): void {
    $offenders = backendLayoutOffenders(['app/Actions'], BACKEND_LAYOUT_REQUEST_NEEDLES, BACKEND_LAYOUT_REQUEST_ALLOWLIST);

    expect($offenders)->toBeEmpty("Pass validated values from the controller, not the Request.\n".implode("\n", $offenders));
});

test('no ResourceServices folder comes back', function (): void {
    expect(base_path('app/Services/ResourceServices'))->not->toBeDirectory();
});
