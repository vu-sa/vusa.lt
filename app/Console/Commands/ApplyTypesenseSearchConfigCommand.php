<?php

namespace App\Console\Commands;

use App\Services\Typesense\TypesenseCollectionConfig;
use App\Services\Typesense\TypesenseCuration;
use App\Services\Typesense\TypesenseSynonyms;
use Illuminate\Console\Command;
use Typesense\Client;

/**
 * Applies the Typesense synonym and curation sets and attaches them to every
 * collection. Idempotent — safe to re-run. Must run after a reindex, since
 * recreating a collection drops its set attachments.
 */
class ApplyTypesenseSearchConfigCommand extends Command
{
    protected $signature = 'typesense:apply-search-config';

    protected $description = 'Upsert the Typesense synonym/curation sets and attach them to all collections';

    public function handle(): int
    {
        $client = new Client(config('scout.typesense.client-settings'));

        $this->info('Upserting synonym set ('.TypesenseSynonyms::SET_NAME.')...');
        TypesenseSynonyms::upsertSynonymSet($client);

        $this->info('Upserting curation set ('.TypesenseCuration::SET_NAME.')...');
        TypesenseCuration::upsertCurationSet($client);

        $this->info('Attaching sets to collections...');
        $rows = [];
        foreach (TypesenseCollectionConfig::getAllCollectionNames() as $collection) {
            try {
                $client->collections[$collection]->update([
                    'synonym_sets' => [TypesenseSynonyms::SET_NAME],
                    'curation_sets' => [TypesenseCuration::SET_NAME],
                ]);
                $rows[] = [$collection, '✅'];
            } catch (\Exception $e) {
                $rows[] = [$collection, '❌ '.$e->getMessage()];
            }
        }

        $this->table(['Collection', 'Status'], $rows);
        $this->info('🎉 Search config applied.');

        return self::SUCCESS;
    }
}
