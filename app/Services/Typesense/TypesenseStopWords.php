<?php

namespace App\Services\Typesense;

use Typesense\Client;

/**
 * Configuration for Typesense stop words.
 *
 * Stop words are common words that are excluded from search queries
 * because they don't add meaning to the search and can slow things down.
 *
 * NOTE: Be careful with stop words - removing too many common words
 * can make exact phrase matching less effective.
 *
 * @see https://typesense.org/docs/27.1/api/stopwords.html
 */
class TypesenseStopWords
{
    /**
     * Lithuanian stop words - common words that don't add search value
     */
    public const LITHUANIAN_STOP_WORDS = [
        'ir',       // and
        'arba',     // or
        'bet',      // but
        'kad',      // that
        'kai',      // when
        'kaip',     // how
        'kur',      // where
        'yra',      // is
        'buvo',     // was
        'bus',      // will be
        'su',       // with
        'be',       // without
        'iš',       // from
        'į',        // to/into
        'per',      // through
        'po',       // after
        'prie',     // at/near
        'dėl',      // because of
        'apie',     // about
        'nuo',      // from
        'iki',      // until
        'tik',      // only
        'jau',      // already
        'dar',      // still/yet
        'net',      // even
        'taip',     // yes/so
        'ne',       // no/not
        'tai',      // this
        'to',       // of that
        'tos',      // of those
        'tas',      // that (m.)
        'ta',       // that (f.)
        'tie',      // those (m.)
        'jis',      // he
        'ji',       // she
        'jie',      // they (m.)
        'jos',      // they (f.)
        'mes',      // we
        'jūs',      // you (pl.)
        'šis',      // this (m.)
        'ši',       // this (f.)
        'šie',      // these (m.)
    ];

    /**
     * English stop words - basic common words
     */
    public const ENGLISH_STOP_WORDS = [
        'a',
        'an',
        'the',
        'and',
        'or',
        'but',
        'is',
        'are',
        'was',
        'were',
        'be',
        'been',
        'being',
        'have',
        'has',
        'had',
        'do',
        'does',
        'did',
        'will',
        'would',
        'could',
        'should',
        'may',
        'might',
        'must',
        'shall',
        'can',
        'of',
        'at',
        'by',
        'for',
        'with',
        'about',
        'against',
        'between',
        'into',
        'through',
        'during',
        'before',
        'after',
        'above',
        'below',
        'to',
        'from',
        'up',
        'down',
        'in',
        'out',
        'on',
        'off',
        'over',
        'under',
        'again',
        'further',
        'then',
        'once',
        'here',
        'there',
        'when',
        'where',
        'why',
        'how',
        'all',
        'each',
        'few',
        'more',
        'most',
        'other',
        'some',
        'such',
        'no',
        'nor',
        'not',
        'only',
        'own',
        'same',
        'so',
        'than',
        'too',
        'very',
        'just',
        'also',
    ];

    /**
     * Get all stop words combined for bilingual search
     */
    public static function getAllStopWords(): array
    {
        return array_merge(self::LITHUANIAN_STOP_WORDS, self::ENGLISH_STOP_WORDS);
    }

    /**
     * Create/update stop words set for a collection
     *
     * @param  Client  $client  Typesense client
     * @param  string  $setName  Name for the stop words set
     * @param  array  $stopWords  Array of stop words
     * @param  string|null  $locale  Optional locale (e.g., 'lt', 'en')
     */
    public static function upsertStopWordsSet(
        Client $client,
        string $setName,
        array $stopWords,
        ?string $locale = null
    ): array {
        $params = [
            'name' => $setName,
            'stopwords' => $stopWords,
        ];

        if ($locale) {
            $params['locale'] = $locale;
        }

        try {
            return $client->stopwords->put($params);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Setup default stop words sets for VU SA platform
     */
    public static function setupDefaultStopWords(Client $client): array
    {
        $results = [];

        // Lithuanian stop words
        $results['lt'] = self::upsertStopWordsSet(
            $client,
            'vusa_lt_stopwords',
            self::LITHUANIAN_STOP_WORDS,
            'lt'
        );

        // English stop words
        $results['en'] = self::upsertStopWordsSet(
            $client,
            'vusa_en_stopwords',
            self::ENGLISH_STOP_WORDS,
            'en'
        );

        // Combined bilingual stop words (for collections that don't have locale field)
        $results['combined'] = self::upsertStopWordsSet(
            $client,
            'vusa_combined_stopwords',
            self::getAllStopWords()
        );

        return $results;
    }

    /**
     * Retrieve all stop words sets
     */
    public static function getAllStopWordsSets(Client $client): array
    {
        try {
            return $client->stopwords->getAll();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Delete a stop words set
     */
    public static function deleteStopWordsSet(Client $client, string $setName): array
    {
        try {
            return $client->stopwords[$setName]->delete();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
