<?php

namespace App\Console\Commands;

use App\Enums\SearchableModelEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Typesense\Client;

class ReindexSearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'search:reindex {model?} {--dry-run : Show which models would be reindexed without actually doing it}';

    /**
     * The console command description.
     */
    protected $description = 'Reindex models for search (recreates Typesense collections to update schemas)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $models = $this->argument('model')
            ? ["App\\Models\\{$this->argument('model')}"]
            : SearchableModelEnum::getAllModelClasses();

        if (empty($models)) {
            $this->warn('No Typesense-enabled models found.');

            return;
        }

        if ($this->option('dry-run')) {
            $this->info('🔍 Models that would be reindexed:');
            foreach ($models as $model) {
                $engine = $this->getModelSearchEngine($model);
                $this->line("  - {$model} (using {$engine})");
            }

            return;
        }

        $this->info('🔍 Starting search index reindexing...');

        foreach ($models as $model) {
            $engine = $this->getModelSearchEngine($model);
            $this->info("Reindexing {$model} (using {$engine})...");

            try {
                if ($engine === 'TypesenseEngine') {
                    $this->reindexTypesenseModel($model);
                } else {
                    $this->reindexDatabaseModel($model);
                }

                $this->info("✅ {$model} reindexed successfully");
            } catch (\Exception $e) {
                $this->error("❌ Failed to reindex {$model}: ".$e->getMessage());
            }
        }

        $this->info('🎉 Reindexing completed!');
    }

    /**
     * Get the search engine type for a model
     */
    private function getModelSearchEngine(string $model): string
    {
        $instance = new $model;
        $engine = $instance->searchableUsing();

        return class_basename(get_class($engine));
    }

    /**
     * Reindex a Typesense model by deleting and recreating the collection
     */
    private function reindexTypesenseModel(string $model): void
    {
        $collectionName = (new $model)->searchableAs();

        try {
            // Delete the collection to force schema recreation
            $client = new Client(config('scout.typesense.client-settings'));
            $client->collections[$collectionName]->delete();
            $this->line("  - Deleted collection '{$collectionName}' to update schema");
        } catch (\Typesense\Exceptions\ObjectNotFound $e) {
            // Collection doesn't exist yet, which is fine for first run
            $this->line("  - Collection '{$collectionName}' not found (will be created)");
        } catch (\Exception $e) {
            // Other errors (connection, auth, etc.) - log but continue
            $this->warn("  - Could not delete collection '{$collectionName}': ".$e->getMessage());
        }

        // Import will recreate the collection with the current schema
        Artisan::call('scout:import', ['model' => $model]);

        // Verify collection was created
        try {
            $client = new Client(config('scout.typesense.client-settings'));
            $collection = $client->collections[$collectionName]->retrieve();
            $docCount = $collection['num_documents'] ?? 0;
            $this->line("  - Recreated collection with fresh schema and data ({$docCount} documents)");
        } catch (\Exception $e) {
            $this->line('  - Recreated collection with fresh schema and data');
        }
    }

    /**
     * Reindex a database model using traditional flush/import
     */
    private function reindexDatabaseModel(string $model): void
    {
        Artisan::call('scout:flush', ['model' => $model]);
        $this->line('  - Flushed existing index');

        Artisan::call('scout:import', ['model' => $model]);
        $this->line('  - Imported fresh data');
    }
}
