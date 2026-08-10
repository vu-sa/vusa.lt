<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Typesense\Client;

/**
 * Drops the Typesense collections the test suite leaves behind.
 *
 * `TestingServiceProvider` clears the prefixes a run is about to use, but only
 * those — a run with fewer parallel processes than the previous one strands the
 * higher tokens' collections, and one-off prefixes set inside a test file are
 * never reclaimed. This prunes all of them.
 */
#[Description('Delete Typesense collections left behind by the test suite')]
#[Signature('typesense:prune-test-collections {--dry-run : List the collections that would be deleted without deleting them}')]
class PruneTestTypesenseCollections extends Command
{
    /**
     * Collection name prefixes owned by the test suite.
     *
     * @var list<string>
     */
    private const TEST_PREFIXES = ['testing_', 'test_'];

    public function __construct(private readonly Client $client)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Refusing to run in production.');

            return self::FAILURE;
        }

        try {
            $collections = $this->client->collections->retrieve();
        } catch (\Throwable $e) {
            $this->error('Could not reach Typesense: '.$e->getMessage());

            return self::FAILURE;
        }

        $stale = array_values(array_filter(
            $collections,
            fn (array $collection): bool => $this->isTestCollection($collection['name'])
        ));

        if ($stale === []) {
            $this->info('✅ No test collections to prune.');

            return self::SUCCESS;
        }

        $documents = array_sum(array_column($stale, 'num_documents'));

        if ($this->option('dry-run')) {
            $this->info(sprintf('🔍 %d test collection(s) holding %d document(s) would be deleted:', count($stale), $documents));

            foreach ($stale as $collection) {
                $this->line("  - {$collection['name']} ({$collection['num_documents']} docs)");
            }

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($stale as $collection) {
            try {
                $this->client->collections[$collection['name']]->delete();
            } catch (\Throwable $e) {
                $failed++;
                $this->error("❌ Failed to delete {$collection['name']}: ".$e->getMessage());
            }
        }

        $this->info(sprintf('✅ Pruned %d test collection(s), freeing %d document(s).', count($stale) - $failed, $documents));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function isTestCollection(string $name): bool
    {
        foreach (self::TEST_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
