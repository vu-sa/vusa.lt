<?php

namespace App\Services\Typesense;

use Typesense\Client;

/**
 * Configuration for Typesense synonyms.
 *
 * Synonyms allow searching for related terms to return matching results.
 * For example, searching "VU" will also match "Vilniaus universitetas".
 *
 * Typesense 30+ exposes synonyms as a top-level "synonym set" resource
 * (`/synonym_sets`) that is attached to collections, replacing the old
 * collection-nested `/collections/{c}/synonyms` endpoints.
 *
 * @see https://typesense.org/docs/latest/api/synonyms.html
 */
class TypesenseSynonyms
{
    /**
     * Name of the synonym set holding all VU SR synonyms. Attached to every
     * collection by the `typesense:apply-search-config` command.
     */
    public const SET_NAME = 'vusa_synonyms';

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
     * Build the `items` payload for the synonym set from the multi-way and
     * one-way constants. Pure transform — no client needed.
     *
     * @return array<int, array{id: string, synonyms: array<int, string>, root?: string}>
     */
    public static function buildSynonymSetItems(): array
    {
        $items = [];

        foreach (self::MULTI_WAY_SYNONYMS as $synonym) {
            $items[] = [
                'id' => $synonym['id'],
                'synonyms' => $synonym['synonyms'],
            ];
        }

        foreach (self::ONE_WAY_SYNONYMS as $synonym) {
            $items[] = [
                'id' => $synonym['id'],
                'root' => $synonym['root'],
                'synonyms' => $synonym['synonyms'],
            ];
        }

        return $items;
    }

    /**
     * Create or update the synonym set with all configured synonyms.
     */
    public static function upsertSynonymSet(Client $client): array
    {
        return $client->synonymSets->upsert(self::SET_NAME, [
            'items' => self::buildSynonymSetItems(),
        ]);
    }

    /**
     * Retrieve the synonym set.
     */
    public static function getSynonymSet(Client $client): array
    {
        try {
            return $client->synonymSets[self::SET_NAME]->retrieve();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Delete the synonym set.
     */
    public static function deleteSynonymSet(Client $client): array
    {
        try {
            return $client->synonymSets[self::SET_NAME]->delete();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
