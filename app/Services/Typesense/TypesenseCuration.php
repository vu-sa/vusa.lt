<?php

namespace App\Services\Typesense;

use Typesense\Client;

/**
 * Typesense curation (pinning/promoting/excluding results).
 *
 * Curation lets you pin specific documents to the top of search results for
 * specific queries, or exclude certain documents from appearing.
 *
 * Typesense 30+ exposes curation as a top-level "curation set" resource
 * (`/curation_sets`) that is attached to collections, replacing the old
 * collection-nested `/collections/{c}/overrides` endpoints. The `override_tags`
 * parameter was also renamed to `curation_tags` (not used here).
 *
 * The set (`vusa_curation`) is attached to every collection by the
 * `typesense:apply-search-config` command. It ships empty — use {@see pinItem}
 * and {@see excludeItem} to build items and pass them to {@see upsertCurationSet}
 * once real query → result mappings are known.
 *
 * Use cases for VU SR:
 * - Pin important announcements when users search for related terms
 * - Pin official documents when searching for policies
 * - Exclude outdated/test content from search results
 *
 * @see https://typesense.org/docs/latest/api/curation.html
 */
class TypesenseCuration
{
    /**
     * Name of the curation set attached to collections.
     */
    public const SET_NAME = 'vusa_curation';

    /**
     * Build a curation item that pins a document to a position for a query.
     * Pure transform — no client needed.
     *
     * @return array{id: string, rule: array{query: string, match: string}, includes: array<int, array{id: string, position: int}>}
     */
    public static function pinItem(
        string $id,
        string $query,
        string $documentId,
        int $position = 1,
        string $match = 'exact'
    ): array {
        return [
            'id' => $id,
            'rule' => [
                'query' => $query,
                'match' => $match,
            ],
            'includes' => [
                ['id' => $documentId, 'position' => $position],
            ],
        ];
    }

    /**
     * Build a curation item that excludes a document from a query's results.
     * Pure transform — no client needed.
     *
     * @return array{id: string, rule: array{query: string, match: string}, excludes: array<int, array{id: string}>}
     */
    public static function excludeItem(
        string $id,
        string $query,
        string $documentId,
        string $match = 'exact'
    ): array {
        return [
            'id' => $id,
            'rule' => [
                'query' => $query,
                'match' => $match,
            ],
            'excludes' => [
                ['id' => $documentId],
            ],
        ];
    }

    /**
     * Create or update the curation set. Defaults to an empty set (the
     * mechanism is wired and attached, ready to receive real pins later).
     *
     * @param  array<int, array<string, mixed>>  $items  Items from {@see pinItem} / {@see excludeItem}.
     */
    public static function upsertCurationSet(Client $client, array $items = []): array
    {
        return $client->curationSets->upsert(self::SET_NAME, [
            'items' => $items,
        ]);
    }

    /**
     * Retrieve the curation set.
     */
    public static function getCurationSet(Client $client): array
    {
        try {
            return $client->curationSets[self::SET_NAME]->retrieve();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Delete the curation set.
     */
    public static function deleteCurationSet(Client $client): array
    {
        try {
            return $client->curationSets[self::SET_NAME]->delete();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
