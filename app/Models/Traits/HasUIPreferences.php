<?php

namespace App\Models\Traits;

/**
 * Trait for managing user UI preferences (pinned and recently visited pages).
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
     * Maximum number of pinned pages kept per user.
     */
    public static int $maxPinnedPages = 10;

    /**
     * Default UI preferences structure.
     *
     * Note: `recent_pages` is intentionally kept empty here. `array_replace_recursive`
     * merges lists element-wise, so a non-empty default would corrupt the stored list.
     */
    protected function getDefaultUIPreferences(): array
    {
        return [
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
