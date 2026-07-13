<?php

namespace App\Console\Commands;

use App\Enums\SearchableModelEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;

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
    public function handle(): int
    {
        $models = $this->argument('model')
            ? ["App\\Models\\{$this->argument('model')}"]
            : SearchableModelEnum::getAllModelClasses();

        if (empty($models)) {
            $this->warn('No Typesense-enabled models found.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('🔍 Models that would be reindexed:');
            foreach ($models as $model) {
                $engine = $this->getModelSearchEngine($model);
                $this->line("  - {$model} (using {$engine})");
            }

            return self::SUCCESS;
        }

        $this->info('🔍 Starting search index reindexing...');

        // Import synchronously. With scout.queue on (the default), scout:import only
        // dispatches MakeSearchable jobs, so the collection is not recreated until a
        // worker picks them up — and the search config below would then be applied to
        // collections that do not exist yet.
        if (config('scout.queue')) {
            $this->line('  (importing synchronously — scout.queue is bypassed for this command)');
            config(['scout.queue' => false]);
        }

        $failed = 0;

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
                $failed++;
                $this->error("❌ Failed to reindex {$model}: ".$e->getMessage());
            }
        }

        // Recreating collections drops their synonym/curation set attachments,
        // so re-apply the search config afterwards.
        $this->info('Re-applying Typesense search config (synonyms, curation)...');
        $configExit = Artisan::call('typesense:apply-search-config', [], $this->getOutput());

        if ($failed > 0 || $configExit !== self::SUCCESS) {
            $this->newLine();
            $this->error("❌ Reindexing finished with problems ({$failed} model(s) failed).");

            return self::FAILURE;
        }

        $this->info('🎉 Reindexing completed!');

        return self::SUCCESS;
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
        } catch (ObjectNotFound $e) {
            // Collection doesn't exist yet, which is fine for first run
            $this->line("  - Collection '{$collectionName}' not found (will be created)");
        } catch (\Exception $e) {
            // Other errors (connection, auth, etc.) - log but continue
            $this->warn("  - Could not delete collection '{$collectionName}': ".$e->getMessage());
        }

        // Import will recreate the collection with the current schema
        Artisan::call('scout:import', ['model' => $model]);

        // Verify the collection really came back. Reporting success here without
        // checking is how a silently-missing collection used to reach the attach step
        // and fail there with a much less obvious error.
        $client = new Client(config('scout.typesense.client-settings'));
        $collection = $client->collections[$collectionName]->retrieve();
        $docCount = $collection['num_documents'] ?? 0;

        $this->line("  - Recreated collection with fresh schema and data ({$docCount} documents)");
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
