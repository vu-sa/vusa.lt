<?php

/**
 * Localized URL segments live in three places that have to agree:
 *
 *  1. App\Support\LocalizedRouteSlugs — the registry;
 *  2. routes/web.php — the routes that declare the parameters and constrain them;
 *  3. resources/js/Utils/LocalizedRoutes.ts — the frontend mirror, needed because Ziggy only
 *     carries the *current* language's defaults.
 *
 * A drift between them is invisible until a URL 404s or a language toggle points at the wrong
 * page, so it is asserted here rather than trusted.
 */

use App\Support\LocalizedRouteSlugs;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

/**
 * The frontend mirror, parsed out of the TypeScript source.
 *
 * @return array{slugs: array<string, array<string, string>>, routes: array<string, array<int, string>>}
 */
function localizedRouteSlugsFromTypeScript(): array
{
    $source = (string) file_get_contents(resource_path('js/Utils/LocalizedRoutes.ts'));

    $slugs = [];
    preg_match('/LOCALIZED_ROUTE_SLUGS[^=]*= \{(.*?)\n\};/s', $source, $slugBlock);

    preg_match_all(
        '/(\w+): \{\s*lt: \'([^\']+)\',\s*en: \'([^\']+)\',?\s*\}/s',
        $slugBlock[1] ?? '',
        $matches,
        PREG_SET_ORDER
    );

    foreach ($matches as $match) {
        $slugs[$match[1]] = ['lt' => $match[2], 'en' => $match[3]];
    }

    $routes = [];
    preg_match('/ROUTE_SLUG_PARAMETERS[^=]*= \{(.*?)\n\};/s', $source, $routeBlock);

    preg_match_all("/'?([\w.]+)'?: \[([^\]]*)\]/", $routeBlock[1] ?? '', $routeMatches, PREG_SET_ORDER);

    foreach ($routeMatches as $match) {
        preg_match_all("/'([^']+)'/", $match[2], $parameters);
        $routes[$match[1]] = $parameters[1];
    }

    return ['slugs' => $slugs, 'routes' => $routes];
}

test('every registered slug parameter is used by a route', function (): void {
    $declared = [];

    foreach (Route::getRoutes() as $route) {
        foreach ($route->parameterNames() as $parameter) {
            if (isset(LocalizedRouteSlugs::SLUGS[$parameter])) {
                $declared[$parameter] = true;
            }
        }
    }

    expect(array_keys($declared))->toEqualCanonicalizing(array_keys(LocalizedRouteSlugs::SLUGS));
});

test('routes constrain their localized segments to the registered slugs', function (): void {
    $unconstrained = [];

    foreach (Route::getRoutes() as $route) {
        foreach ($route->parameterNames() as $parameter) {
            if (! isset(LocalizedRouteSlugs::SLUGS[$parameter])) {
                continue;
            }

            $pattern = $route->wheres[$parameter] ?? null;
            $accepted = LocalizedRouteSlugs::accepted($parameter);

            // whereIn() compiles to an alternation of the accepted values.
            foreach ($accepted as $slug) {
                if ($pattern === null || ! str_contains($pattern, $slug)) {
                    $unconstrained[] = "{$route->getName()}: {$parameter} does not accept '{$slug}'";
                }
            }
        }
    }

    expect($unconstrained)->toBe([]);
});

test('the TypeScript mirror holds the same slugs as the PHP registry', function (): void {
    ['slugs' => $slugs] = localizedRouteSlugsFromTypeScript();

    expect($slugs)->toEqual(LocalizedRouteSlugs::SLUGS);
});

test('the TypeScript route map matches what the routes actually declare', function (): void {
    ['routes' => $routes] = localizedRouteSlugsFromTypeScript();

    $drift = [];

    foreach ($routes as $name => $parameters) {
        $route = Route::getRoutes()->getByName($name);

        if ($route === null) {
            $drift[] = "{$name}: no such route";

            continue;
        }

        $actual = array_values(array_filter(
            $route->parameterNames(),
            fn (string $parameter): bool => isset(LocalizedRouteSlugs::SLUGS[$parameter])
        ));

        sort($actual);
        sort($parameters);

        if ($actual !== $parameters) {
            $drift[] = "{$name}: TS says [".implode(', ', $parameters).'], routes say ['.implode(', ', $actual).']';
        }
    }

    expect($drift)->toBe([]);
});

test('route() fills the current language slug from URL defaults', function (): void {
    app()->setLocale('lt');
    URL::defaults(LocalizedRouteSlugs::defaults('lt'));

    expect(route('documents', ['subdomain' => 'www', 'lang' => 'lt']))->toContain('/lt/dokumentai');

    app()->setLocale('en');
    URL::defaults(LocalizedRouteSlugs::defaults('en'));

    expect(route('documents', ['subdomain' => 'www', 'lang' => 'en']))->toContain('/en/documents');
});

test('LocalizedRouteSlugs::route builds a URL in the requested language', function (): void {
    app()->setLocale('lt');
    URL::defaults(LocalizedRouteSlugs::defaults('lt'));

    expect(LocalizedRouteSlugs::route('newsArchive', ['subdomain' => 'www'], 'en'))
        ->toContain('/en/news')
        ->and(LocalizedRouteSlugs::route('contacts.studentRepresentatives', ['subdomain' => 'www'], 'en'))
        ->toContain('/en/contacts/student-representatives');
});
