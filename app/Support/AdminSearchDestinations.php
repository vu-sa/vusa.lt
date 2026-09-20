<?php

namespace App\Support;

use App\Models\Calendar;
use App\Models\Document;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Resource;
use App\Models\User;

/**
 * Where "show all results" for a search collection leads (O1).
 *
 * The cross-entity search page no longer carries a tab per entity: each entity with a collection
 * page of its own is reached there. The query key is the one thing that differs — Typesense
 * collection pages read `q`, database table pages read `search` (BaseIndexRequest).
 */
final class AdminSearchDestinations
{
    /**
     * One row per multi-search result key. `route` is the page that replaced the collection's search
     * tab; null while the search page itself still serves that tab (agenda items and resources have
     * no page of their own until Phase 9).
     *
     * @var array<string, array{tab: string, route: string|null, model: class-string, queryKey: string}>
     */
    private const array COLLECTIONS = [
        'meetings' => ['tab' => 'meetings', 'route' => 'meetings.index', 'model' => Meeting::class, 'queryKey' => 'q'],
        'agendaItems' => ['tab' => 'agenda-items', 'route' => null, 'model' => Meeting::class, 'queryKey' => 'q'],
        'institutions' => ['tab' => 'institutions', 'route' => 'institutions.index', 'model' => Institution::class, 'queryKey' => 'search'],
        'resources' => ['tab' => 'resources', 'route' => null, 'model' => Resource::class, 'queryKey' => 'q'],
        'duties' => ['tab' => 'duties', 'route' => 'duties.index', 'model' => Duty::class, 'queryKey' => 'search'],
        'documents' => ['tab' => 'documents', 'route' => 'documents.index', 'model' => Document::class, 'queryKey' => 'search'],
        'news' => ['tab' => 'news', 'route' => 'news.index', 'model' => News::class, 'queryKey' => 'search'],
        'pages' => ['tab' => 'pages', 'route' => 'pages.index', 'model' => Page::class, 'queryKey' => 'search'],
        'calendar' => ['tab' => 'calendar', 'route' => 'calendar.index', 'model' => Calendar::class, 'queryKey' => 'search'],
        'users' => ['tab' => 'users', 'route' => 'users.index', 'model' => User::class, 'queryKey' => 'search'],
    ];

    /**
     * The page that replaced a legacy tab, or null when the search page still serves it (or the tab is unknown).
     */
    public static function pageUrl(string $tab, ?string $query): ?string
    {
        foreach (self::COLLECTIONS as $collection) {
            if ($collection['tab'] === $tab && $collection['route'] !== null) {
                return route($collection['route'], filled($query) ? [$collection['queryKey'] => $query] : [], false);
            }
        }

        return null;
    }

    /**
     * What the frontend needs to link each result group to its full list. `href` is null when the
     * user may not open that list, so a group never offers a link that would 403.
     *
     * @return array<string, array{href: string|null, queryKey: string}> keyed by the multi-search result key
     */
    public static function forUser(User $user): array
    {
        return array_map(fn (array $collection): array => [
            'href' => $user->can('viewAny', $collection['model'])
                ? ($collection['route'] !== null
                    ? route($collection['route'], [], false)
                    : route('search.index', ['tab' => $collection['tab']], false))
                : null,
            'queryKey' => $collection['queryKey'],
        ], self::COLLECTIONS);
    }
}
