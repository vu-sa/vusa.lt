<?php

namespace App\Services;

use App\Models\Navigation;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    private const int CACHE_TTL = 3600;

    /**
     * Up to how many columns the public footer renders — each column is one
     * `location: footer` root, so this also caps how many such roots are allowed.
     */
    public const int FOOTER_MAX_COLUMNS = 4;

    private const array LOCALES = ['lt', 'en'];

    public static function getNavigationForPublic()
    {
        $locale = app()->getLocale();

        return Cache::remember("navigation:public:{$locale}", self::CACHE_TTL, function () use ($locale) {
            $navigation = Navigation::where('lang', $locale)
                ->where('is_active', true)
                ->orderBy('order')
                ->get();

            // Footer roots share the same `parent_id = 0` shape as header roots but are
            // rendered separately (see getFooterNavigationForPublic()) — exclude them here
            // so a footer column never also shows up as a header mega-menu trigger.
            $rootNavigation = $navigation->where('parent_id', 0)
                ->reject(fn (Navigation $root) => self::isFooterLocation($root))
                ->values()
                ->toArray();

            // Build children on root navigation elements
            for ($i = 0; $i < count($rootNavigation); $i++) {

                // Roots carry their own presentation settings too (e.g. `cols`,
                // `icon` for the menubar trigger) — hoist the same way children get.
                $rootNavigation[$i] = self::hoistExtraAttributes($rootNavigation[$i]);

                // The structure that the UI uses is that the root navigation elements have a 'links' property which is an array of max. 3 arrays.
                // Each array of the links array represents a column in the navigation.
                // The information about which column the link should be in is stored in the 'extra_attributes->column' property of the link.
                $rootNavigation[$i]['links'] = [];

                // Get immediate children of root navigation element
                $children = $navigation->where('parent_id', $rootNavigation[$i]['id'])->values()->toArray();

                // Expand extra_attributes to own keys to make it easier to work with
                // Other data in the extra_attributes array will be used in the UI
                foreach ($children as $key => $child) {
                    $children[$key] = self::hoistExtraAttributes($child);
                }

                // Set the links of the root navigation by columns
                for ($j = 1; $j <= 3; $j++) {

                    // Push array to root links, where extra_attributes['column'] == $j
                    $rootNavigation[$i]['links'][] = array_filter($children, function ($child) use ($j) {

                        // Also check if the column is not set, then it should be in the first column
                        if (! isset($child['column'])) {
                            return $j == 1;
                        }

                        return $child['column'] == $j;
                    });

                    $rootNavigation[$i]['links'][$j - 1] = array_values($rootNavigation[$i]['links'][$j - 1]);
                }

                // Remove empty arrays and re-index so the frontend receives a real array
                $rootNavigation[$i]['links'] = array_values(array_filter($rootNavigation[$i]['links']));

                // Add column count immediately for the front end
                $rootNavigation[$i]['cols'] = count($rootNavigation[$i]['links']);
            }

            return $rootNavigation;
        });
    }

    /**
     * Footer navigation, cached the same way as the header. Structurally simpler than
     * the header tree: each `location: footer` root is one column (title +
     * optional link — see NavigationRequest, a footer root without a URL renders as
     * plain text), and its children are always simple links, so there is no per-child
     * column assignment to hoist.
     *
     * @return array<int, array{id: int, name: string, url: string, links: array<int, array{id: int, name: string, url: string, new_tab: bool}>}>
     */
    public static function getFooterNavigationForPublic(): array
    {
        $locale = app()->getLocale();

        return Cache::remember("navigation:footer:{$locale}", self::CACHE_TTL, function () use ($locale) {
            $navigation = Navigation::where('lang', $locale)
                ->where('is_active', true)
                ->orderBy('order')
                ->get();

            return $navigation->where('parent_id', 0)
                ->filter(fn (Navigation $root) => self::isFooterLocation($root))
                ->values()
                ->take(self::FOOTER_MAX_COLUMNS)
                ->map(fn (Navigation $root): array => [
                    'id' => $root->id,
                    'name' => $root->name,
                    'url' => $root->url,
                    'links' => $navigation->where('parent_id', $root->id)
                        ->values()
                        ->map(fn (Navigation $link): array => [
                            'id' => $link->id,
                            'name' => $link->name,
                            'url' => $link->url,
                            'new_tab' => (bool) ($link->extra_attributes['new_tab'] ?? false),
                        ])
                        ->all(),
                ])
                ->all();
        });
    }

    /**
     * Full, uncached navigation tree for a given language, for the admin builder.
     *
     * Unlike `getNavigationForPublic()` this:
     * - is not cached (the builder must always see the latest edit),
     * - keeps `extra_attributes` intact instead of hoisting its keys,
     * - keeps all three columns present (including empty ones), so the builder can
     *   still offer an empty column as a drop target,
     * - includes inactive items so editors can find and re-activate them.
     *
     * Footer roots (`location: footer`) are excluded — they have their own admin
     * surface via {@see getFooterTreeForAdmin()} and don't fit this tree's fixed
     * 3-column, drag-and-drop shape.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getTreeForAdmin(string $lang): array
    {
        $navigation = Navigation::where('lang', $lang)->orderBy('order')->get();

        $rootNavigation = $navigation->where('parent_id', 0)
            ->reject(fn (Navigation $root) => self::isFooterLocation($root))
            ->values();

        return $rootNavigation->map(function (Navigation $root) use ($navigation) {
            $children = $navigation->where('parent_id', $root->id)->values();

            $columns = [];
            for ($column = 1; $column <= 3; $column++) {
                $columns[] = $children
                    ->filter(fn (Navigation $child) => ($child->extra_attributes['column'] ?? 1) == $column)
                    ->values()
                    ->toArray();
            }

            $data = $root->toArray();
            $data['links'] = $columns;
            $data['cols'] = $root->extra_attributes['cols'] ?? count(array_filter($columns));

            return $data;
        })->all();
    }

    /**
     * Uncached footer tree for the admin footer manager — every column (including an
     * inactive one) and its children, `extra_attributes` intact.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getFooterTreeForAdmin(string $lang): array
    {
        $navigation = Navigation::where('lang', $lang)->orderBy('order')->get();

        return $navigation->where('parent_id', 0)
            ->filter(fn (Navigation $root) => self::isFooterLocation($root))
            ->values()
            ->map(function (Navigation $root) use ($navigation): array {
                $data = $root->toArray();
                $data['links'] = $navigation->where('parent_id', $root->id)->values()->toArray();

                return $data;
            })
            ->all();
    }

    /**
     * A root belongs to the footer when explicitly tagged; every other root (including
     * one predating this key entirely) is a header trigger. Keep in sync with
     * NavigationRequest, which stamps this key on every save.
     */
    private static function isFooterLocation(Navigation $root): bool
    {
        return ($root->extra_attributes['location'] ?? 'header') === 'footer';
    }

    /**
     * Hoists a navigation row's `extra_attributes` keys (type, column, cols,
     * icon, …) onto the row itself and drops the now-redundant nested key, so the
     * frontend reads `item.cols` / `link.type` directly instead of digging
     * through `item.extra_attributes.cols`.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function hoistExtraAttributes(array $row): array
    {
        $extraAttributes = $row['extra_attributes'] ?? null;
        unset($row['extra_attributes']);

        if ($extraAttributes === null) {
            return $row;
        }

        foreach ($extraAttributes as $key => $value) {
            $row[$key] = $value;
        }

        return $row;
    }

    /**
     * Clear navigation cache for all locales.
     *
     * The single invalidation entry point — the model's `saved`/`deleted`/`restored`
     * hooks call this directly rather than juggling a second cache layer.
     */
    public static function clearCache(): void
    {
        foreach (self::LOCALES as $locale) {
            Cache::forget("navigation:public:{$locale}");
            Cache::forget("navigation:footer:{$locale}");
        }
    }
}
