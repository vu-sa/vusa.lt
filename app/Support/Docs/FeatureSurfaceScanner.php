<?php

namespace App\Support\Docs;

use App\Support\MorphMap;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Groups the app's routed surface into the feature areas an admin recognises,
 * and joins each to its model, its inline `_parts` help, the tests that exercise
 * it and the pages that document it.
 *
 * The route area (`reservations`), the morph alias (`reservation`) and the help
 * directory (`reservations`) are the same feature spelt three ways; a single
 * `Str::snake(Str::singular())` normaliser reconciles them.
 */
class FeatureSurfaceScanner
{
    /**
     * Route names never exercised by a feature test on purpose: dev tooling,
     * framework plumbing and binary/file responses. Shared with the command so
     * the reportable surface is defined in exactly one place.
     *
     * @var list<string>
     */
    public const IGNORED_PREFIXES = [
        'boost', 'sanctum', 'ignition', 'debugbar', 'horizon',
        'telescope', 'livewire', 'storage', 'pulse',
    ];

    /**
     * Whether a route name belongs in the report at all. Public `api.v1.*` is
     * covered by tests/Feature/Api and excluded, but admin API (`api.v1.admin.*`)
     * backs real admin features and is kept, folded into its feature area.
     */
    public static function isReportable(string $name): bool
    {
        if (str_starts_with($name, 'api.') && ! str_starts_with($name, 'api.v1.admin.')) {
            return false;
        }

        return array_all(self::IGNORED_PREFIXES, fn ($ignored) => ! ($name === $ignored || str_starts_with($name, $ignored.'.')));
    }

    public function scan(TestSurface $surface, DocClaims $claims): FeatureSurface
    {
        $helpDirs = $this->helpAliases();
        $modelAliases = array_keys(MorphMap::MAP);

        /** @var array<string, array{routes: list<string>, admin: bool}> $grouped */
        $grouped = [];

        /** @var RoutingRoute $route */
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if ($name === null || ! self::isReportable($name)) {
                continue;
            }

            $slug = $this->areaOf($name);
            $grouped[$slug]['routes'][] = $name;
            $grouped[$slug]['admin'] = ($grouped[$slug]['admin'] ?? false)
                || str_starts_with(ltrim($route->uri(), '/'), 'mano');
        }

        $areas = [];

        foreach ($grouped as $slug => $data) {
            $routes = array_values(array_unique($data['routes']));
            sort($routes);

            $alias = $this->aliasFor($slug, $modelAliases);
            $tested = array_values(array_filter($routes, fn ($r) => isset($surface->routes[$r])));

            $areas[$slug] = new FeatureArea(
                slug: $slug,
                modelAlias: $alias,
                modelClass: $alias !== null ? MorphMap::classFor($alias) : null,
                routes: $routes,
                testedRoutes: $tested,
                hasHelp: $alias !== null && in_array($alias, $helpDirs, true),
                docPages: $this->pagesDocumenting($slug, $alias, $claims),
                isAdmin: $data['admin'] ?? false,
            );
        }

        ksort($areas);

        return new FeatureSurface($areas);
    }

    /**
     * Pages that document an area — by intentional declaration only. A page owns
     * an area if its `area:` frontmatter names the slug, or a class in its
     * `models:` list resolves to the area's model. The old transitive route join
     * is gone: a reservation page whose tests incidentally hit an approval route
     * does not thereby document approvals.
     *
     * @return list<string>
     */
    private function pagesDocumenting(string $slug, ?string $alias, DocClaims $claims): array
    {
        $pages = $claims->pagesForArea($slug);

        if ($alias !== null) {
            foreach ($claims->meta as $page => $facts) {
                foreach ($facts['models'] as $model) {
                    if (Str::snake(Str::singular(class_basename($model))) === $alias) {
                        $pages[] = $page;
                        break;
                    }
                }
            }
        }

        $pages = array_values(array_unique($pages));
        sort($pages);

        return $pages;
    }

    /**
     * The feature area a route belongs to. Admin API routes are folded into the
     * same area as their web counterpart (`api.v1.admin.reservations.store` →
     * `reservations`) rather than piling up under a meaningless `api`.
     */
    private function areaOf(string $routeName): string
    {
        if (str_starts_with($routeName, 'api.v1.admin.')) {
            $routeName = substr($routeName, strlen('api.v1.admin.'));
        }

        return Str::before($routeName, '.') ?: $routeName;
    }

    /**
     * @param  list<string>  $modelAliases
     */
    private function aliasFor(string $slug, array $modelAliases): ?string
    {
        $normalised = Str::snake(Str::singular($slug));

        return in_array($normalised, $modelAliases, true) ? $normalised : null;
    }

    /**
     * The morph aliases that have a `docs/_parts/<dir>` inline-help fragment.
     *
     * @return list<string>
     */
    private function helpAliases(): array
    {
        $partsDir = base_path('docs/_parts');

        if (! is_dir($partsDir)) {
            return [];
        }

        $aliases = [];

        foreach (scandir($partsDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..' || ! is_dir($partsDir.'/'.$entry)) {
                continue;
            }

            $aliases[] = Str::snake(Str::singular($entry));
        }

        return $aliases;
    }
}
