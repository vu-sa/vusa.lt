<?php

/**
 * Admin page props come from a presenter with an exact shape, so Wayfinder can type them. The
 * holdouts below may only shrink: migrate a method, then delete its line.
 *
 * @see .ai/rules/payloads.md
 */

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

/** Patterns that build a payload outside a presenter. */
const PAGE_PAYLOAD_LEGACY_PATTERNS = [
    '/->(?:toArray|toFullArray)\(\)/',
    '/->(?:map|flatMap|mapWithKeys|transform)\(\s*(?:static\s+)?(?:fn|function)\b/',
    '/->only\(/',
    '/\bSerialize[A-Z]\w*::/',
    '/\w+Resource::collection\(|\bnew\s+\w+Resource\(/',
];

/** `Controller@method` actions that still render legacy payloads. */
const PAGE_PAYLOAD_HOLDOUTS = [
    'AgendaItemController@show',
    'AtstovavimasDashboardController@atstovavimas',
    'AtstovavimasDashboardController@padaliniai',
    'BannerController@edit',
    'BannerController@index',
    'CadenceController@index',
    'CalendarController@create',
    'CalendarController@edit',
    'CalendarController@index',
    'DashboardController@index',
    'DocumentController@index',
    'DutiableTimelineController@index',
    'DutyController@edit',
    'DutyController@index',
    'DutyController@show',
    'DutyTypeController@edit',
    'DutyTypeController@show',
    'EventTypeController@index',
    'FormController@create',
    'FormController@edit',
    'FormController@show',
    'GoalController@create',
    'GoalController@edit',
    'GoalController@index',
    'GoalController@show',
    'InstitutionController@edit',
    'InstitutionController@index',
    'InstitutionTypeController@edit',
    'InstitutionTypeController@show',
    'KnowledgeItemController@create',
    'KnowledgeItemController@edit',
    'KnowledgeItemController@show',
    'LearningStepController@create',
    'LearningStepController@edit',
    'MailingListController@renderIndex',
    'MailingListController@show',
    'MeetingController@show',
    'MySupportRequestController@create',
    'NavigationController@create',
    'NavigationController@edit',
    'NavigationController@index',
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
    'QuickLinkController@create',
    'QuickLinkController@edit',
    'RepresentativesController@index',
    'ReservationController@show',
    'ResourceCategoryController@index',
    'ResourceController@edit',
    'ResourceController@show',
    'ResponsibilityController@index',
    'ResponsibilityController@show',
    'SistemaDashboardController@index',
    'StudyProgramController@edit',
    'StudySetController@edit',
    'StudySetController@index',
    'SupportRequestController@edit',
    'SupportRequestController@show',
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

        $lines = file($file->getPathname());
        $bodies = [];

        foreach ((new ReflectionClass($class))->getMethods() as $method) {
            // Trait methods report the using class but the trait's file and lines.
            if ($method->getDeclaringClass()->getName() === $class && $method->getFileName() === $file->getRealPath()) {
                $bodies[$method->getName()] = pagePayloadCode(array_slice($lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));
            }
        }

        foreach ($bodies as $name => $body) {
            if (! str_contains($body, 'Inertia::render')) {
                continue;
            }

            // Props are often built in a helper on the same controller, so its body counts too.
            preg_match_all('/(?:\$this->|self::|static::)(\w+)\(/', $body, $calls);
            $code = implode("\n", [$body, ...array_map(fn (string $called): string => $called === $name ? '' : ($bodies[$called] ?? ''), $calls[1])]);

            if (array_any(PAGE_PAYLOAD_LEGACY_PATTERNS, fn (string $pattern): bool => preg_match($pattern, $code) === 1)) {
                $actions[] = Str::after($class, 'Admin\\').'@'.$name;
            }
        }
    }

    sort($actions);

    return $actions;
}

/**
 * @param  list<string>  $lines
 */
function pagePayloadCode(array $lines): string
{
    $tokens = token_get_all('<?php '.implode('', $lines));

    return implode('', array_map(
        fn (array|string $token): string => is_array($token) ? (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true) ? '' : $token[1]) : $token,
        array_slice($tokens, 1),
    ));
}

it('builds new admin page payloads in presenters', function () {
    $unlisted = array_values(array_diff(pagePayloadLegacyActions(), PAGE_PAYLOAD_HOLDOUTS));

    expect($unlisted)->toBe([], 'Build these page props in an app/Services/<Domain>/*Presenter.php with an exact @return shape (.ai/rules/payloads.md): '.implode(', ', $unlisted));
});

it('lists only holdouts that still build legacy payloads', function () {
    $migrated = array_values(array_diff(PAGE_PAYLOAD_HOLDOUTS, pagePayloadLegacyActions()));

    expect($migrated)->toBe([], 'Remove migrated actions from PAGE_PAYLOAD_HOLDOUTS: '.implode(', ', $migrated));
});
