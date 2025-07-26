<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ReindexSearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'search:reindex {model?} {--dry-run : Show which models would be reindexed without actually doing it}';

    /**
     * The console command description.
     */
    protected $description = 'Reindex models for Typesense search';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $models = $this->argument('model')
            ? ["App\\Models\\{$this->argument('model')}"]
            : $this->getTypesenseModels();

        if (empty($models)) {
            $this->warn('No Typesense-enabled models found.');

            return;
        }

        if ($this->option('dry-run')) {
            $this->info('🔍 Typesense models that would be reindexed:');
            foreach ($models as $model) {
                $this->line("  - {$model}");
            }

            return;
        }

        $this->info('🔍 Starting Typesense search index reindexing...');

        foreach ($models as $model) {
            $this->info("Reindexing {$model}...");

            try {
                Artisan::call('scout:flush', ['model' => $model]);
                $this->line('  - Flushed existing index');

                Artisan::call('scout:import', ['model' => $model]);
                $this->line('  - Imported fresh data');

                $this->info("✅ {$model} reindexed successfully");
            } catch (\Exception $e) {
                $this->error("❌ Failed to reindex {$model}: ".$e->getMessage());
            }
        }

        $this->info('🎉 Reindexing completed!');
    }

    /**
     * Get models that use Typesense search engine
     */
    private function getTypesenseModels(): array
    {
        // Simple array of known Typesense models
        // Add new models here when they implement Typesense search
        return [
            \App\Models\News::class,
            \App\Models\Page::class,
            \App\Models\Document::class,
            \App\Models\Calendar::class,
        ];
    }
}
