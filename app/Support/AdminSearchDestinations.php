<?php

namespace App\Support;

/**
 * Destinations for old search URLs. Typesense collections read `q`; database lists read `search`.
 */
final class AdminSearchDestinations
{
    /**
     * One row per legacy search tab.
     *
     * @var array<string, array{tab: string, route: string, queryKey: string}>
     */
    private const array COLLECTIONS = [
        'meetings' => ['tab' => 'meetings', 'route' => 'meetings.index', 'queryKey' => 'q'],
        'agendaItems' => ['tab' => 'agenda-items', 'route' => 'agendaItems.index', 'queryKey' => 'q'],
        'institutions' => ['tab' => 'institutions', 'route' => 'institutions.index', 'queryKey' => 'q'],
        'resources' => ['tab' => 'resources', 'route' => 'resources.index', 'queryKey' => 'q'],
        'duties' => ['tab' => 'duties', 'route' => 'duties.index', 'queryKey' => 'q'],
        'documents' => ['tab' => 'documents', 'route' => 'documents.index', 'queryKey' => 'search'],
        'news' => ['tab' => 'news', 'route' => 'news.index', 'queryKey' => 'search'],
        'pages' => ['tab' => 'pages', 'route' => 'pages.index', 'queryKey' => 'search'],
        'calendar' => ['tab' => 'calendar', 'route' => 'calendar.index', 'queryKey' => 'search'],
        'users' => ['tab' => 'users', 'route' => 'users.index', 'queryKey' => 'q'],
    ];

    /**
     * The collection page for a legacy tab, or null for an unknown tab.
     */
    public static function pageUrl(string $tab, ?string $query): ?string
    {
        foreach (self::COLLECTIONS as $collection) {
            if ($collection['tab'] === $tab) {
                return route($collection['route'], filled($query) ? [$collection['queryKey'] => $query] : [], false);
            }
        }

        return null;
    }
}
