<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\DB;

/**
 * @property array $ui_preferences
 */
trait HasUIPreferences
{
    /**
     * Maximum number of pinned pages kept per user.
     */
    public static int $maxPinnedPages = 10;

    // Non-empty list defaults would merge into stored entries by index.
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

        $this->saveUiPreferences('pinned_pages', fn () => array_slice($sanitized, 0, self::$maxPinnedPages));
    }

    /**
     * Get the recently visited pages, most recent first.
     *
     * @return array<int, array{route: string, params: array, visited_at: string}>
     */
    public function getRecentPages(): array
    {
        return $this->ui_preferences['recent_pages'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function pushRecentPage(
        string $route,
        array $params = [],
        ?string $title = null,
        ?string $url = null,
        int $max = 15
    ): void {
        $this->saveUiPreferences('recent_pages', function (array $recent) use ($route, $params, $title, $url, $max): array {
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

            return array_slice($recent, 0, $max);
        });
    }

    /**
     * Clear the recently visited pages list.
     */
    public function clearRecentPages(): void
    {
        $this->saveUiPreferences('recent_pages', fn () => []);
    }

    private function saveUiPreferences(string $key, callable $update): void
    {
        DB::transaction(function () use ($key, $update): void {
            $locked = $this->newQuery()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $preferences = $locked->ui_preferences;
            $preferences[$key] = $update($preferences[$key] ?? []);
            $locked->fill(['ui_preferences' => $preferences])->saveQuietly();
            $this->setAttribute('ui_preferences', $preferences);
            $this->syncOriginalAttribute('ui_preferences');
        });
    }
}
