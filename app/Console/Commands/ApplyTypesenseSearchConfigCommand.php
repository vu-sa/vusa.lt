<?php

namespace App\Console\Commands;

use App\Services\Typesense\TypesenseCollectionConfig;
use App\Services\Typesense\TypesenseCuration;
use App\Services\Typesense\TypesenseSynonyms;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
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

    /**
     * Attempts per collection before giving up.
     */
    private const MAX_ATTEMPTS = 4;

    public function handle(): int
    {
        $client = new Client(config('scout.typesense.client-settings'));

        $this->info('Upserting synonym set ('.TypesenseSynonyms::SET_NAME.')...');
        TypesenseSynonyms::upsertSynonymSet($client);

        $this->info('Upserting curation set ('.TypesenseCuration::SET_NAME.')...');
        TypesenseCuration::upsertCurationSet($client);

        $this->info('Attaching sets to collections...');

        $rows = [];
        $failed = 0;

        foreach (TypesenseCollectionConfig::getAllCollectionNames() as $collection) {
            $error = $this->attachSets($client, $collection);

            if ($error === null) {
                $rows[] = [$collection, '✅'];

                continue;
            }

            $failed++;
            $rows[] = [$collection, '❌ '.$this->summarise($error)];
        }

        $this->table(['Collection', 'Status'], $rows);

        if ($failed > 0) {
            $this->newLine();
            $this->error("❌ {$failed} collection(s) could not be configured.");
            $this->line('If they were reported missing, the collections do not exist yet — reindex first.');

            return self::FAILURE;
        }

        $this->info('🎉 Search config applied.');

        return self::SUCCESS;
    }

    /**
     * Attach both sets to a collection, retrying transient failures.
     *
     * Returns null on success, or the last error message.
     */
    private function attachSets(Client $client, string $collection): ?string
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $client->collections[$collection]->update([
                    'synonym_sets' => [TypesenseSynonyms::SET_NAME],
                    'curation_sets' => [TypesenseCuration::SET_NAME],
                ]);

                return null;
            } catch (\Exception $e) {
                $message = $e->getMessage();

                // A missing collection will not appear by retrying.
                if (! $this->isTransient($message) || $attempt === self::MAX_ATTEMPTS) {
                    return $message;
                }

                // Typesense is often reached through a rate-limiting proxy, so back off
                // rather than hammering it: 1s, 2s, 4s.
                $wait = 2 ** ($attempt - 1);
                $this->line("  - {$collection}: rate limited, retrying in {$wait}s...");
                sleep($wait);
            }
        }

        return 'Exhausted retries';
    }

    /**
     * Rate limiting and gateway hiccups are worth another try; a 404 is not.
     */
    private function isTransient(string $message): bool
    {
        return Str::contains($message, [
            '429', 'Too Many Requests',
            '502', '503', '504',
            'Bad Gateway', 'Service Unavailable', 'Gateway Time-out',
        ], ignoreCase: true);
    }

    /**
     * Proxies answer with full HTML error pages; a table cell needs one line.
     */
    private function summarise(string $message): string
    {
        if (Str::contains($message, '<html')) {
            $title = Str::between($message, '<title>', '</title>');

            return $title !== '' ? trim($title).' (from the proxy in front of Typesense)' : 'HTTP error from proxy';
        }

        return Str::limit(trim($message), 120);
    }
}
