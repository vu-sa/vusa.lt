<?php

namespace App\Models\Traits;

/**
 * Trait for managing user UI preferences (sidebar customization + recently visited pages).
 *
 * Add to User model:
 * - Use this trait
 * - Add 'ui_preferences' to $casts as 'array'
 *
 * @property array $ui_preferences
 */
trait HasUIPreferences
{
    /**
     * Toggleable sidebar sections. Anything not in this list is locked
     * (header, account dropdown, main navigation) and cannot be hidden.
     *
     * @var list<string>
     */
    public static array $toggleableSidebarSections = [
        'pinned',
        'quick_actions',
        'recently_visited',
        'followed_institutions',
        'spacer',
        'start_fm',
        'secondary',
    ];

    /**
     * Maximum number of pinned pages kept per user.
     */
    public static int $maxPinnedPages = 10;

    /**
     * Allowed sidebar density values.
     *
     * @var list<string>
     */
    public static array $densityValues = ['comfortable', 'compact'];

    /**
     * Toggleable quick-action keys. Mirrors the actions defined in
     * NavQuickActions.vue / useCommandActions.ts.
     *
     * @var list<string>
     */
    public static array $toggleableQuickActions = [
        'new_problem',
        'new_meeting',
        'new_news',
        'new_reservation',
        'duty_update',
    ];

    /**
     * Default UI preferences structure.
     *
     * Note: `recent_pages` is intentionally kept empty here. `array_replace_recursive`
     * merges lists element-wise, so a non-empty default would corrupt the stored list.
     */
    protected function getDefaultUIPreferences(): array
    {
        $sections = [];
        foreach (self::$toggleableSidebarSections as $section) {
            $sections[$section] = true;
        }

        // Followed institutions is disabled by default.
        $sections['followed_institutions'] = false;

        $quickActions = [];
        foreach (self::$toggleableQuickActions as $action) {
            $quickActions[$action] = true;
        }

        return [
            'sidebar' => [
                'sections' => $sections,
                'order' => [
                    'pinned',
                    'quick_actions',
                    'recently_visited',
                    'followed_institutions',
                    'spacer',
                    'start_fm',
                    'secondary',
                ],
                'collapsed' => false,
            ],
            'quick_actions' => $quickActions,
            'appearance' => [
                'density' => 'comfortable',
            ],
            'pinned_pages' => [],
            'recent_pages' => [],
        ];
    }

    /**
     * Get UI preferences with defaults applied.
     */
    public function getUiPreferencesAttribute($value): array
    {
        $preferences = $value ? (is_string($value) ? json_decode($value, true) : $value) : [];

        return array_replace_recursive($this->getDefaultUIPreferences(), $preferences);
    }

    /**
     * Get the sidebar section visibility map (only toggleable sections).
     *
     * @return array<string, bool>
     */
    public function getSidebarSectionVisibility(): array
    {
        $stored = $this->ui_preferences['sidebar']['sections'] ?? [];

        $visibility = [];
        foreach (self::$toggleableSidebarSections as $section) {
            $visibility[$section] = (bool) ($stored[$section] ?? true);
        }

        return $visibility;
    }

    /**
     * Replace the sidebar section visibility map. Unknown keys are discarded.
     *
     * @param  array<string, mixed>  $sections
     */
    public function setSidebarSectionVisibility(array $sections): void
    {
        $preferences = $this->ui_preferences;

        $visibility = $preferences['sidebar']['sections'] ?? [];
        foreach ($sections as $key => $value) {
            if (in_array($key, self::$toggleableSidebarSections, true)) {
                $visibility[$key] = (bool) $value;
            }
        }

        $preferences['sidebar']['sections'] = $visibility;
        $this->update(['ui_preferences' => $preferences]);
    }

    /**
     * Get the sidebar section order (all toggleable sections, sanitized, deduped).
     *
     * @return list<string>
     */
    public function getSidebarSectionOrder(): array
    {
        $stored = $this->ui_preferences['sidebar']['order'] ?? [];

        $seen = [];
        $ordered = [];

        foreach ($stored as $key) {
            if (in_array($key, self::$toggleableSidebarSections, true) && ! in_array($key, $seen, true)) {
                $seen[] = $key;
                $ordered[] = $key;
            }
        }

        foreach (self::$toggleableSidebarSections as $section) {
            if (! in_array($section, $seen, true)) {
                $ordered[] = $section;
            }
        }

        return $ordered;
    }

    /**
     * Replace the sidebar section order. Unknown keys are discarded,
     * duplicates are removed, and missing toggleable sections are appended.
     *
     * @param  array<int, mixed>  $order
     */
    public function setSidebarSectionOrder(array $order): void
    {
        $seen = [];
        $sanitized = [];

        foreach ($order as $key) {
            if (is_string($key) && in_array($key, self::$toggleableSidebarSections, true) && ! in_array($key, $seen, true)) {
                $seen[] = $key;
                $sanitized[] = $key;
            }
        }

        foreach (self::$toggleableSidebarSections as $section) {
            if (! in_array($section, $seen, true)) {
                $sanitized[] = $section;
            }
        }

        $preferences = $this->ui_preferences;
        $preferences['sidebar']['order'] = $sanitized;
        $this->update(['ui_preferences' => $preferences]);
    }

    /**
     * Get the quick-action visibility map.
     *
     * @return array<string, bool>
     */
    public function getQuickActionVisibility(): array
    {
        $stored = $this->ui_preferences['quick_actions'] ?? [];

        $visibility = [];
        foreach (self::$toggleableQuickActions as $action) {
            $visibility[$action] = (bool) ($stored[$action] ?? true);
        }

        return $visibility;
    }

    /**
     * Replace the quick-action visibility map. Unknown keys are discarded.
     *
     * @param  array<string, mixed>  $actions
     */
    public function setQuickActionVisibility(array $actions): void
    {
        $preferences = $this->ui_preferences;

        $visibility = $preferences['quick_actions'] ?? [];
        foreach ($actions as $key => $value) {
            if (in_array($key, self::$toggleableQuickActions, true)) {
                $visibility[$key] = (bool) $value;
            }
        }

        $preferences['quick_actions'] = $visibility;
        $this->update(['ui_preferences' => $preferences]);
    }

    /**
     * Get whether the sidebar is collapsed (icon-only) for this user.
     */
    public function getSidebarCollapsed(): bool
    {
        return (bool) ($this->ui_preferences['sidebar']['collapsed'] ?? false);
    }

    /**
     * Persist the sidebar collapsed (icon-only) state.
     */
    public function setSidebarCollapsed(bool $collapsed): void
    {
        $preferences = $this->ui_preferences;
        $preferences['sidebar']['collapsed'] = $collapsed;
        $this->update(['ui_preferences' => $preferences]);
    }

    /**
     * Get the sidebar density. Falls back to 'comfortable' for unknown values.
     */
    public function getDensity(): string
    {
        $density = $this->ui_preferences['appearance']['density'] ?? 'comfortable';

        return in_array($density, self::$densityValues, true) ? $density : 'comfortable';
    }

    /**
     * Persist the sidebar density. Unknown values are ignored.
     */
    public function setDensity(string $density): void
    {
        if (! in_array($density, self::$densityValues, true)) {
            return;
        }

        $preferences = $this->ui_preferences;
        $preferences['appearance']['density'] = $density;
        $this->update(['ui_preferences' => $preferences]);
    }

    /**
     * Get the pinned pages, in user order.
     *
     * @return array<int, array{route: string, params: array, title: string|null, url: string|null}>
     */
    public function getPinnedPages(): array
    {
        return $this->ui_preferences['pinned_pages'] ?? [];
    }

    /**
     * Replace the pinned pages list. Sanitizes each entry (requires a string
     * route), dedupes by path/route identity, and caps at $maxPinnedPages.
     *
     * @param  array<int, mixed>  $pages
     */
    public function setPinnedPages(array $pages): void
    {
        $seen = [];
        $sanitized = [];

        foreach ($pages as $page) {
            if (! is_array($page) || ! isset($page['route']) || ! is_string($page['route'])) {
                continue;
            }

            $params = (isset($page['params']) && is_array($page['params'])) ? $page['params'] : [];
            $url = (isset($page['url']) && is_string($page['url'])) ? $page['url'] : null;
            $title = (isset($page['title']) && is_string($page['title'])) ? $page['title'] : null;

            // Identity is the path when known (query string excluded); otherwise route + params.
            $identity = $url ?? ($page['route'].'|'.json_encode($params));
            if (in_array($identity, $seen, true)) {
                continue;
            }
            $seen[] = $identity;

            $sanitized[] = [
                'route' => $page['route'],
                'params' => $params,
                'title' => $title,
                'url' => $url,
            ];
        }

        $preferences = $this->ui_preferences;
        $preferences['pinned_pages'] = array_slice($sanitized, 0, self::$maxPinnedPages);
        $this->update(['ui_preferences' => $preferences]);
    }

    /**
     * Get the recently visited pages, most recent first.
     *
     * @return array<int, array{route: string, params: array, visited_at: string}>
     */
    public function getRecentPages(): array
    {
        // Read directly: the default is an empty list, so the recursive merge in
        // the accessor leaves the stored list intact.
        return $this->ui_preferences['recent_pages'] ?? [];
    }

    /**
     * Push a page onto the recently visited list (dedupes by route + params).
     *
     * @param  array<string, mixed>  $params
     */
    public function pushRecentPage(
        string $route,
        array $params = [],
        ?string $title = null,
        ?string $url = null,
        int $max = 15
    ): void {
        $preferences = $this->ui_preferences;
        $recent = $preferences['recent_pages'] ?? [];

        // Identity is the path when known (query string excluded, so the
        // same page never duplicates); otherwise route + params.
        $newIdentity = $url ?? ($route.'|'.json_encode($params));
        $recent = array_values(array_filter($recent, function ($entry) use ($newIdentity) {
            $entryIdentity = ($entry['url'] ?? null)
                ?? (($entry['route'] ?? '').'|'.json_encode($entry['params'] ?? []));

            return $entryIdentity !== $newIdentity;
        }));

        array_unshift($recent, [
            'route' => $route,
            'params' => $params,
            'title' => $title,
            'url' => $url,
            'visited_at' => now()->toIso8601String(),
        ]);

        $preferences['recent_pages'] = array_slice($recent, 0, $max);
        $this->update(['ui_preferences' => $preferences]);
    }

    /**
     * Clear the recently visited pages list.
     */
    public function clearRecentPages(): void
    {
        $preferences = $this->ui_preferences;
        $preferences['recent_pages'] = [];
        $this->update(['ui_preferences' => $preferences]);
    }
}
