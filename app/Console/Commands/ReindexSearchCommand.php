<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ReindexSearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'search:reindex {model?}';

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
            : $this->getSearchableModels();
            
        foreach ($models as $model) {
            $this->info("Reindexing {$model}...");
            
            try {
                Artisan::call('scout:flush', ['model' => $model]);
                Artisan::call('scout:import', ['model' => $model]);
                $this->info("✅ {$model} reindexed successfully");
            } catch (\Exception $e) {
                $this->error("❌ Failed to reindex {$model}: " . $e->getMessage());
            }
        }
        
        $this->info('🎉 Reindexing completed!');
    }

    /**
     * Get the searchable models
     */
    private function getSearchableModels(): array
    {
        return [
            'App\\Models\\News',
            'App\\Models\\Page',
            'App\\Models\\Document',
            'App\\Models\\Calendar',
        ];
    }
}
