<?php

namespace App\Services\Typesense;

use Illuminate\Support\Facades\Config;
use Typesense\Client;

/**
 * Configuration for Typesense synonyms.
 *
 * Synonyms allow searching for related terms to return matching results.
 * For example, searching "VU" will also match "Vilniaus universitetas".
 *
 * @see https://typesense.org/docs/27.1/api/synonyms.html
 */
class TypesenseSynonyms
{
    /**
     * Multi-way synonyms - all terms are equivalent
     * When any term is searched, documents with any of the other terms will match
     */
    public const MULTI_WAY_SYNONYMS = [
        // University name variations
        [
            'id' => 'vu-variants',
            'synonyms' => ['VU', 'Vilniaus universitetas', 'Vilnius University'],
        ],
        // Student representation organization
        [
            'id' => 'vusa-variants',
            'synonyms' => ['VU SA', 'VUSA', 'Vilniaus universiteto studentų atstovybė', 'Studentų atstovybė'],
        ],
        // Common terms
        [
            'id' => 'meeting-variants',
            'synonyms' => ['posėdis', 'susirinkimas', 'meeting'],
        ],
        [
            'id' => 'student-rep-variants',
            'synonyms' => ['studentų atstovas', 'studentų atstovė', 'atstovas', 'student representative'],
        ],
        [
            'id' => 'agenda-variants',
            'synonyms' => ['darbotvarkė', 'agenda', 'klausimas', 'agenda item'],
        ],
        [
            'id' => 'document-variants',
            'synonyms' => ['dokumentas', 'document', 'failas', 'file'],
        ],
        // Faculty abbreviations (add your faculty-specific synonyms)
        [
            'id' => 'mif-variants',
            'synonyms' => ['MIF', 'Matematikos ir informatikos fakultetas', 'Math and Informatics'],
        ],
        [
            'id' => 'filf-variants',
            'synonyms' => ['FilF', 'Filologijos fakultetas', 'Faculty of Philology'],
        ],
        [
            'id' => 'chgf-variants',
            'synonyms' => ['CHGF', 'Chemijos ir geomokslų fakultetas', 'Chemistry and Geosciences'],
        ],
        [
            'id' => 'evaf-variants',
            'synonyms' => ['EVAF', 'Ekonomikos ir verslo administravimo fakultetas', 'Economics'],
        ],
        [
            'id' => 'fsf-variants',
            'synonyms' => ['FSF', 'Filosofijos fakultetas', 'Faculty of Philosophy'],
        ],
        [
            'id' => 'ff-variants',
            'synonyms' => ['FF', 'Fizikos fakultetas', 'Faculty of Physics'],
        ],
        [
            'id' => 'gmc-variants',
            'synonyms' => ['GMC', 'Gyvybės mokslų centras', 'Life Sciences Center'],
        ],
        [
            'id' => 'kf-variants',
            'synonyms' => ['KF', 'Komunikacijos fakultetas', 'Faculty of Communication'],
        ],
        [
            'id' => 'mf-variants',
            'synonyms' => ['MF', 'Medicinos fakultetas', 'Faculty of Medicine'],
        ],
        [
            'id' => 'tf-variants',
            'synonyms' => ['TF', 'Teisės fakultetas', 'Faculty of Law'],
        ],
        [
            'id' => 'tspmi-variants',
            'synonyms' => ['TSPMI', 'Tarptautinių santykių ir politikos mokslų institutas', 'IIRPS'],
        ],
        [
            'id' => 'vm-variants',
            'synonyms' => ['VM', 'Verslo mokykla', 'Business School'],
        ],
    ];

    /**
     * One-way synonyms - searching for the root term will also find the synonyms
     * But searching for a synonym will NOT find the root term
     */
    public const ONE_WAY_SYNONYMS = [
        // Example: searching "decision" will find "nutarimas", but not vice versa
        [
            'id' => 'decision-lt',
            'root' => 'decision',
            'synonyms' => ['nutarimas', 'sprendimas'],
        ],
        [
            'id' => 'protocol-lt',
            'root' => 'protocol',
            'synonyms' => ['protokolas'],
        ],
        [
            'id' => 'vote-lt',
            'root' => 'vote',
            'synonyms' => ['balsavimas', 'balsas'],
        ],
    ];

    /**
     * Upsert all synonyms to a specific collection
     */
    public static function upsertSynonymsForCollection(Client $client, string $collection): array
    {
        $prefix = Config::get('scout.prefix', '');
        $prefixedCollection = $prefix.$collection;
        $results = [];

        // Upsert multi-way synonyms
        foreach (self::MULTI_WAY_SYNONYMS as $synonym) {
            try {
                $results[$synonym['id']] = $client->collections[$prefixedCollection]
                    ->synonyms
                    ->upsert($synonym['id'], [
                        'synonyms' => $synonym['synonyms'],
                    ]);
            } catch (\Exception $e) {
                $results[$synonym['id']] = ['error' => $e->getMessage()];
            }
        }

        // Upsert one-way synonyms
        foreach (self::ONE_WAY_SYNONYMS as $synonym) {
            try {
                $results[$synonym['id']] = $client->collections[$prefixedCollection]
                    ->synonyms
                    ->upsert($synonym['id'], [
                        'root' => $synonym['root'],
                        'synonyms' => $synonym['synonyms'],
                    ]);
            } catch (\Exception $e) {
                $results[$synonym['id']] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Retrieve all synonyms from a collection
     */
    public static function getSynonymsForCollection(Client $client, string $collection): array
    {
        $prefix = Config::get('scout.prefix', '');
        $prefixedCollection = $prefix.$collection;

        try {
            return $client->collections[$prefixedCollection]->synonyms->retrieve();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Delete a synonym from a collection
     */
    public static function deleteSynonym(Client $client, string $collection, string $synonymId): array
    {
        $prefix = Config::get('scout.prefix', '');
        $prefixedCollection = $prefix.$collection;

        try {
            return $client->collections[$prefixedCollection]->synonyms[$synonymId]->delete();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
