<?php

namespace App\Console\Commands;

use App\Enums\SearchableModelEnum;
use App\Services\Typesense\SearchProfiles;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Engines\TypesenseEngine;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;

#[Description('Reindex models for search (recreates Typesense collections to update schemas)')]
#[Signature('search:reindex {model?} {--dry-run : Show which models would be reindexed without actually doing it}')]
class ReindexSearchCommand extends Command
{
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

        if (config('app.env') === 'staging' && config('scout.prefix') !== 'staging_') {
            $this->error('Refused: staging Typesense operations require SCOUT_PREFIX=staging_.');

            return self::FAILURE;
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

        return $engine instanceof TypesenseEngine ? 'TypesenseEngine' : class_basename($engine::class);
    }

    /**
     * Reindex a Typesense model by deleting and recreating the collection
     */
    private function reindexTypesenseModel(string $model): void
    {
        $collectionName = (new $model)->searchableAs();
        $baseName = substr($collectionName, strlen(config('scout.prefix', '')));
        Cache::forget(SearchProfiles::cacheKey($baseName));

        try {
            // Delete the collection to force schema recreation
            $client = new Client(config('scout.typesense.client-settings'));
            $client->collections[$collectionName]->delete();
            $this->line("  - Deleted collection '{$collectionName}' to update schema");
        } catch (ObjectNotFound) {
            // Collection doesn't exist yet, which is fine for first run
            $this->line("  - Collection '{$collectionName}' not found (will be created)");
        } catch (\Exception $e) {
            // Other errors (connection, auth, etc.) - log but continue
            $this->warn("  - Could not delete collection '{$collectionName}': ".$e->getMessage());
        }

        // Import will recreate the collection with the current schema
        if (Artisan::call('scout:import', ['model' => $model]) !== self::SUCCESS) {
            throw new \RuntimeException('Scout import failed for '.$model);
        }

        // Verify the collection really came back. Reporting success here without
        // checking is how a silently-missing collection used to reach the attach step
        // and fail there with a much less obvious error.
        $client = new Client(config('scout.typesense.client-settings'));
        try {
            $collection = $client->collections[$collectionName]->retrieve();
        } catch (ObjectNotFound) {
            $collection = $client->collections->create(['name' => $collectionName, ...config('scout.typesense.model-settings.'.$model.'.collection-schema')]);
        }
        $docCount = $collection['num_documents'] ?? 0;
        $expected = config('scout.typesense.model-settings.'.$model.'.collection-schema.fields');
        $actual = collect($collection['fields'])->keyBy('name');
        foreach ($expected as $field) {
            // Typesense omits the implicit id field from schema responses.
            if ($field['name'] === 'id') {
                continue;
            }
            if (! $actual->has($field['name'])) {
                throw new \RuntimeException('Recreated schema is missing '.$field['name']);
            }
            foreach (['type', 'stem', 'locale', 'sort', 'facet', 'infix'] as $attribute) {
                if (isset($field[$attribute]) && ($actual[$field['name']][$attribute] ?? null) !== $field[$attribute]) {
                    throw new \RuntimeException('Recreated schema has incompatible '.$field['name'].'.'.$attribute);
                }
            }
        }
        Cache::forever(SearchProfiles::cacheKey($baseName), SearchProfiles::VERSION);

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
