<?php

namespace App\Console\Commands;

use App\Services\Typesense\TypesenseCollectionConfig;
use App\Services\Typesense\TypesenseStopWords;
use App\Services\Typesense\TypesenseSynonyms;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Typesense\Client;

class TypesenseHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'typesense:health 
                            {--detailed : Show detailed collection statistics}
                            {--check-synonyms : Check configured synonyms}
                            {--check-stopwords : Check configured stop words}';

    /**
     * The console command description.
     */
    protected $description = 'Check Typesense health, collections, and configuration status';

    public function __construct(protected Client $client)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Typesense Health Check');
        $this->newLine();

        // 1. Check connection
        if (! $this->checkConnection()) {
            return 1;
        }

        // 2. Check API keys configuration
        $this->checkApiKeys();

        // 3. Check collections
        $this->checkCollections();

        // 4. Optional: Check synonyms
        if ($this->option('check-synonyms')) {
            $this->checkSynonyms();
        }

        // 5. Optional: Check stop words
        if ($this->option('check-stopwords')) {
            $this->checkStopWords();
        }

        $this->newLine();
        $this->info('✅ Health check completed');

        return 0;
    }

    /**
     * Check if Typesense is reachable
     */
    protected function checkConnection(): bool
    {
        $this->info('📡 Connection Status');

        try {
            $health = $this->client->health->retrieve();

            if ($health['ok'] ?? false) {
                $this->line('  ✅ Typesense is healthy and reachable');

                // Get debug info
                $debug = $this->client->debug->retrieve();
                if (isset($debug['version'])) {
                    $this->line("  📌 Version: {$debug['version']}");
                }

                return true;
            }

            $this->error('  ❌ Typesense health check returned not OK');

            return false;
        } catch (\Exception $e) {
            $this->error('  ❌ Cannot connect to Typesense: '.$e->getMessage());
            $this->line('');
            $this->warn('  Check your configuration:');
            $this->line('    - TYPESENSE_HOST: '.Config::get('scout.typesense.client-settings.nodes.0.host', 'not set'));
            $this->line('    - TYPESENSE_PORT: '.Config::get('scout.typesense.client-settings.nodes.0.port', 'not set'));
            $this->line('    - TYPESENSE_PROTOCOL: '.Config::get('scout.typesense.client-settings.nodes.0.protocol', 'not set'));

            return false;
        }
    }

    /**
     * Check API keys configuration
     */
    protected function checkApiKeys(): void
    {
        $this->newLine();
        $this->info('🔑 API Keys Configuration');

        $adminKey = Config::get('scout.typesense.client-settings.api_key');
        $searchOnlyKey = Config::get('scout.typesense.client-settings.search_only_key');
        $adminSearchKey = Config::get('scout.typesense.client-settings.admin_search_key');

        // Check admin key
        if (! empty($adminKey)) {
            $this->line('  ✅ Admin API key is configured');
        } else {
            $this->error('  ❌ Admin API key is missing (TYPESENSE_API_KEY)');
        }

        // Check search-only key (public)
        if (! empty($searchOnlyKey)) {
            $this->line('  ✅ Public search-only key is configured');
        } else {
            $this->warn('  ⚠️  Public search-only key is missing (TYPESENSE_SEARCH_ONLY_KEY)');
        }

        // Check admin search key (for scoped keys)
        if (! empty($adminSearchKey)) {
            $this->line('  ✅ Admin search key is configured');
        } else {
            $this->warn('  ⚠️  Admin search key is missing (will use search_only_key for scoped keys)');
        }

        // Check if keys are valid by trying to list collections
        try {
            $this->client->collections->retrieve();
            $this->line('  ✅ API key has valid permissions');
        } catch (\Exception $e) {
            $this->error('  ❌ API key validation failed: '.$e->getMessage());
        }
    }

    /**
     * Check all collections status
     */
    protected function checkCollections(): void
    {
        $this->newLine();
        $this->info('📚 Collections Status');

        $prefix = Config::get('scout.prefix', '');

        try {
            $collections = $this->client->collections->retrieve();
            $collectionNames = array_column($collections, 'name');

            // Check public collections
            $publicCollections = TypesenseCollectionConfig::getPublicCollectionBaseNames();
            $this->line('');
            $this->line('  <fg=cyan>Public Collections:</>');
            foreach ($publicCollections as $collection) {
                $prefixedName = $prefix.$collection;
                $this->checkCollectionStatus($prefixedName, $collectionNames, $collections);
            }

            // Check admin collections
            $adminCollections = TypesenseCollectionConfig::getAdminCollectionBaseNames();
            $this->line('');
            $this->line('  <fg=cyan>Admin Collections:</>');
            foreach ($adminCollections as $collection) {
                $prefixedName = $prefix.$collection;
                $this->checkCollectionStatus($prefixedName, $collectionNames, $collections);
            }

            // Summary
            $this->newLine();
            $expectedCount = count($publicCollections) + count($adminCollections);
            $existingCount = count(array_filter($collectionNames, fn ($name) => str_starts_with($name, $prefix)));

            if ($existingCount >= $expectedCount) {
                $this->line("  📊 Total: {$existingCount} collections found");
            } else {
                $this->warn("  ⚠️  Expected {$expectedCount} collections, found {$existingCount}");
                $this->line('     Run `sail artisan search:reindex` to create missing collections');
            }

        } catch (\Exception $e) {
            $this->error('  ❌ Failed to retrieve collections: '.$e->getMessage());
        }
    }

    /**
     * Check individual collection status
     */
    protected function checkCollectionStatus(string $name, array $collectionNames, array $collections): void
    {
        if (in_array($name, $collectionNames)) {
            $collection = collect($collections)->firstWhere('name', $name);
            $docCount = $collection['num_documents'] ?? 0;

            if ($this->option('detailed')) {
                $this->line("    ✅ {$name} ({$docCount} docs)");
            } else {
                $status = $docCount > 0 ? '✅' : '⚠️ ';
                $this->line("    {$status} {$name} ({$docCount} docs)");
            }
        } else {
            $this->warn("    ❌ {$name} - MISSING");
        }
    }

    /**
     * Check synonyms configuration
     */
    protected function checkSynonyms(): void
    {
        $this->newLine();
        $this->info('📖 Synonyms Configuration');

        // Typesense 30+: synonyms live in a top-level synonym set, not nested per collection.
        try {
            $set = TypesenseSynonyms::getSynonymSet($this->client);

            if (isset($set['error'])) {
                $this->warn('  ⚠️  Synonym set "'.TypesenseSynonyms::SET_NAME.'" not found (run typesense:apply-search-config)');
            } else {
                $items = $set['items'] ?? [];
                $count = count($items);
                $this->line('  📌 '.TypesenseSynonyms::SET_NAME.": {$count} synonym items");

                if ($this->option('detailed') && $count > 0) {
                    foreach ($items as $synonym) {
                        $terms = implode(' ↔ ', $synonym['synonyms'] ?? [$synonym['root'] ?? '']);
                        $this->line("      - {$synonym['id']}: {$terms}");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->warn('  ⚠️  Could not retrieve synonym set: '.$e->getMessage());
        }

        // Show configured synonyms count
        $configuredMultiWay = count(TypesenseSynonyms::MULTI_WAY_SYNONYMS);
        $configuredOneWay = count(TypesenseSynonyms::ONE_WAY_SYNONYMS);
        $this->line('');
        $this->line("  💡 Configured synonyms: {$configuredMultiWay} multi-way, {$configuredOneWay} one-way");
        $this->line('     Use TypesenseSynonyms::upsertSynonymSet() (or typesense:apply-search-config) to apply');
    }

    /**
     * Check stop words configuration
     */
    protected function checkStopWords(): void
    {
        $this->newLine();
        $this->info('🛑 Stop Words Configuration');

        try {
            $stopwords = $this->client->stopwords->getAll();
            $sets = $stopwords['stopwords'] ?? [];

            if (empty($sets)) {
                $this->warn('  ⚠️  No stop words sets configured');
                $this->line('     Use TypesenseStopWords::setupDefaultStopWords() to create');
            } else {
                foreach ($sets as $set) {
                    $count = count($set['stopwords'] ?? []);
                    $locale = $set['locale'] ?? 'any';
                    $this->line("  📌 {$set['id']}: {$count} words (locale: {$locale})");
                }
            }
        } catch (\Exception $e) {
            $this->warn('  ⚠️  Could not retrieve stop words: '.$e->getMessage());
        }

        // Show configured stop words count
        $ltCount = count(TypesenseStopWords::LITHUANIAN_STOP_WORDS);
        $enCount = count(TypesenseStopWords::ENGLISH_STOP_WORDS);
        $this->line('');
        $this->line("  💡 Available in config: {$ltCount} Lithuanian, {$enCount} English words");
    }
}
