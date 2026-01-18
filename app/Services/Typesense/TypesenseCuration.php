<?php

namespace App\Services\Typesense;

use Illuminate\Support\Facades\Config;
use Typesense\Client;

/**
 * Example configuration for Typesense curation (pinning/promoting results).
 *
 * Curations allow you to pin specific documents to the top of search results
 * for specific queries, or exclude certain documents from appearing.
 *
 * This is NOT IMPLEMENTED yet - this file serves as documentation and
 * a starting point for future implementation.
 *
 * Use cases for VU SA:
 * - Pin important announcements when users search for related terms
 * - Pin official documents when searching for policies
 * - Exclude outdated content from search results
 * - Promote current events in calendar search
 *
 * @see https://typesense.org/docs/27.1/api/curation.html
 *
 * TODO: Implement admin UI for managing curations
 * TODO: Create artisan command for bulk curation management
 */
class TypesenseCuration
{
    /**
     * Example curations for the VU SA platform
     *
     * Structure:
     * - name: Unique identifier for the curation
     * - collection: Which collection this applies to
     * - match_query: The search query that triggers this curation (supports * wildcard)
     * - includes: Documents to pin to the top (by document ID, with position)
     * - excludes: Documents to hide from results (by document ID)
     */
    public const EXAMPLE_CURATIONS = [
        // Example: When searching for "nuostatai" (regulations), pin the main regulations document
        [
            'name' => 'pin-main-regulations',
            'collection' => 'documents',
            'match_query' => 'nuostatai',
            'includes' => [
                ['id' => 'DOC_ID_HERE', 'position' => 1], // Replace with actual document ID
            ],
            'excludes' => [],
        ],

        // Example: When searching for "atstovavimas" (representation), show key info
        [
            'name' => 'pin-representation-info',
            'collection' => 'pages',
            'match_query' => 'atstovavimas*', // Matches atstovavimas, atstovavimo, etc.
            'includes' => [
                ['id' => 'PAGE_ID_HERE', 'position' => 1],
            ],
            'excludes' => [],
        ],

        // Example: Exclude test/draft documents from search
        [
            'name' => 'exclude-test-documents',
            'collection' => 'documents',
            'match_query' => '*', // Applies to all searches
            'includes' => [],
            'excludes' => [
                ['id' => 'TEST_DOC_1'],
                ['id' => 'TEST_DOC_2'],
            ],
        ],
    ];

    /**
     * Create or update a curation rule
     *
     * @param  Client  $client  Typesense client
     * @param  string  $collection  Collection name (without prefix)
     * @param  string  $name  Curation rule name
     * @param  string  $matchQuery  Query that triggers this curation
     * @param  array  $includes  Documents to pin: [['id' => '123', 'position' => 1]]
     * @param  array  $excludes  Documents to hide: [['id' => '456']]
     */
    public static function upsertCuration(
        Client $client,
        string $collection,
        string $name,
        string $matchQuery,
        array $includes = [],
        array $excludes = []
    ): array {
        $prefix = Config::get('scout.prefix', '');
        $prefixedCollection = $prefix.$collection;

        $params = [
            'rule' => [
                'query' => $matchQuery,
                'match' => 'exact', // or 'contains' for partial matching
            ],
        ];

        if (! empty($includes)) {
            $params['includes'] = $includes;
        }

        if (! empty($excludes)) {
            $params['excludes'] = $excludes;
        }

        try {
            return $client->collections[$prefixedCollection]
                ->overrides
                ->upsert($name, $params);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get all curation rules for a collection
     */
    public static function getCurations(Client $client, string $collection): array
    {
        $prefix = Config::get('scout.prefix', '');
        $prefixedCollection = $prefix.$collection;

        try {
            return $client->collections[$prefixedCollection]->overrides->retrieve();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Delete a curation rule
     */
    public static function deleteCuration(Client $client, string $collection, string $name): array
    {
        $prefix = Config::get('scout.prefix', '');
        $prefixedCollection = $prefix.$collection;

        try {
            return $client->collections[$prefixedCollection]->overrides[$name]->delete();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Pin a specific document to the top of search results for a query
     *
     * Convenience method for common use case.
     */
    public static function pinDocument(
        Client $client,
        string $collection,
        string $documentId,
        string $forQuery,
        int $position = 1
    ): array {
        $curationName = 'pin-'.md5($forQuery.'-'.$documentId);

        return self::upsertCuration(
            $client,
            $collection,
            $curationName,
            $forQuery,
            [['id' => $documentId, 'position' => $position]],
            []
        );
    }

    /**
     * Exclude a document from all search results
     *
     * Convenience method for hiding content without deleting it.
     */
    public static function excludeDocument(
        Client $client,
        string $collection,
        string $documentId
    ): array {
        $curationName = 'exclude-'.$documentId;

        return self::upsertCuration(
            $client,
            $collection,
            $curationName,
            '*', // Applies to all searches
            [],
            [['id' => $documentId]]
        );
    }
}
