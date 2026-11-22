<?php

/**
 * Admin page props come from a presenter with an exact shape, so Wayfinder can type them. The
 * holdouts below may only shrink: migrate a method, then delete its line.
 *
 * @see .ai/rules/payloads.md
 */

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

/** Source fragments that build a payload outside a presenter. */
const PAGE_PAYLOAD_LEGACY_TOKENS = ['->toArray()', '->toFullArray()', '->map(fn', '->map(function', '->transform(', 'Serialize'];

/** `Controller@method` actions that still render legacy payloads. */
const PAGE_PAYLOAD_HOLDOUTS = [
    'AgendaItemController@show',
    'AtstovavimasDashboardController@atstovavimas',
    'BannerController@edit',
    'BannerController@index',
    'CalendarController@create',
    'CalendarController@edit',
    'CalendarController@index',
    'DashboardController@index',
    'DocumentController@index',
    'DutiableTimelineController@index',
    'DutyController@edit',
    'DutyController@index',
    'DutyTypeController@edit',
    'DutyTypeController@show',
    'EventTypeController@index',
    'FormController@edit',
    'GoalController@edit',
    'GoalController@index',
    'GoalController@show',
    'InstitutionController@edit',
    'InstitutionController@index',
    'InstitutionTypeController@edit',
    'InstitutionTypeController@show',
    'MailingListController@renderIndex',
    'NewsController@create',
    'NewsController@edit',
    'OrganizacijaDashboardController@index',
    'PageController@create',
    'PageController@edit',
    'ProblemCategoryController@index',
    'ProblemController@create',
    'ProblemController@edit',
    'ProblemController@index',
    'ProblemController@show',
    'ProfileController@userSettings',
    'RepresentativesController@index',
    'ReservationController@show',
    'ResourceCategoryController@index',
    'ResourceController@edit',
    'ResourceController@show',
    'ResponsibilityController@index',
    'ResponsibilityController@show',
    'StudyProgramController@edit',
    'StudySetController@edit',
    'StudySetController@index',
    'TagController@edit',
    'TagController@index',
    'TaskController@renderCollection',
    'TenantController@index',
    'UserController@edit',
    'UserController@index',
    'UserController@show',
];

/**
 * @return list<string> `Controller@method` of every admin action rendering a legacy payload
 */
function pagePayloadLegacyActions(): array
{
    $actions = [];

    foreach (Finder::create()->files()->in(app_path('Http/Controllers/Admin'))->name('*.php') as $file) {
        $class = 'App\\Http\\Controllers\\Admin\\'.str_replace('/', '\\', Str::beforeLast($file->getRelativePathname(), '.php'));

        if (! class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);
        $lines = file($file->getPathname());

        foreach ($reflection->getMethods() as $method) {
            // Trait methods report the using class but the trait's file and lines.
            if ($method->getDeclaringClass()->getName() !== $class || $method->getFileName() !== $file->getRealPath()) {
                continue;
            }

            $body = implode('', array_slice($lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));

            if (str_contains($body, 'Inertia::render') && array_any(PAGE_PAYLOAD_LEGACY_TOKENS, fn (string $token): bool => str_contains($body, $token))) {
                $actions[] = Str::after($class, 'Admin\\').'@'.$method->getName();
            }
        }
    }

    sort($actions);

    return $actions;
}

it('builds new admin page payloads in presenters', function () {
    $unlisted = array_values(array_diff(pagePayloadLegacyActions(), PAGE_PAYLOAD_HOLDOUTS));

    expect($unlisted)->toBe([], 'Build these page props in an app/Services/<Domain>/*Presenter.php with an exact @return shape (.ai/rules/payloads.md): '.implode(', ', $unlisted));
});

it('lists only holdouts that still build legacy payloads', function () {
    $migrated = array_values(array_diff(PAGE_PAYLOAD_HOLDOUTS, pagePayloadLegacyActions()));

    expect($migrated)->toBe([], 'Remove migrated actions from PAGE_PAYLOAD_HOLDOUTS: '.implode(', ', $migrated));
});
