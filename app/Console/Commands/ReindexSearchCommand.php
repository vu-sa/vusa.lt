<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Symfony\Component\Console\Helper\ProgressBar;

class ReindexSearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reindex-search {--model= : Specific model to reindex} {--log : Log output to search.log}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush and import all models using Laravel Scout Searchable trait';

    /**
     * Statistics tracking
     */
    protected array $stats = [
        'successful' => [],
        'failed' => [],
        'skipped' => [],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting search reindexing process...');
        $startTime = now();
        
        $specificModel = $this->option('model');
        $shouldLog = $this->option('log');
        $searchableModels = $this->getSearchableModels($specificModel);
        
        if (empty($searchableModels)) {
            $message = 'No searchable models found' . ($specificModel ? " matching '$specificModel'" : '');
            $this->error($message);
            if ($shouldLog) {
                Log::channel('daily')->error($message);
            }
            return 1;
        }
        
        $message = 'Found ' . count($searchableModels) . ' models using Laravel Scout';
        $this->info($message);
        if ($shouldLog) {
            Log::channel('daily')->info($message);
        }
        
        // Display all models to be processed
        $this->table(['Model'], array_map(fn($model) => [class_basename($model)], $searchableModels));
        
        foreach ($searchableModels as $model) {
            $shortName = class_basename($model);
            $this->processModel($model, $shortName, $shouldLog);
        }
        
        $endTime = now();
        $duration = $endTime->diffInSeconds($startTime);
        
        $this->displaySummary($duration, $shouldLog);
        
        return 0;
    }
    
    /**
     * Process a single model by flushing and importing.
     *
     * @param string $model
     * @param string $shortName
     * @param bool $shouldLog
     * @return void
     */
    protected function processModel(string $model, string $shortName, bool $shouldLog): void
    {
        $this->newLine();
        $this->info("Processing model: $shortName");
        if ($shouldLog) {
            Log::channel('daily')->info("Processing model: $shortName");
        }
        
        try {
            // Flush the model's index
            $this->output->write("<fg=yellow>[$shortName]</> Flushing search index...");
            $this->callSilent('scout:flush', [
                'model' => $model
            ]);
            $this->output->writeln(" <fg=green>Done</>");
            
            // Import the model's data to the index
            $this->output->write("<fg=yellow>[$shortName]</> Importing records to search index...");
            $this->callSilent('scout:import', [
                'model' => $model
            ]);
            $this->output->writeln(" <fg=green>Done</>");
            
            // Record success
            $this->stats['successful'][] = $shortName;
            
            // Log completion
            $message = "Successfully processed $shortName";
            if ($shouldLog) {
                Log::channel('daily')->info($message);
            }
        } catch (\Exception $e) {
            $this->stats['failed'][] = $shortName;
            $errorMessage = "Error processing $shortName: " . $e->getMessage();
            $this->error($errorMessage);
            if ($shouldLog) {
                Log::channel('daily')->error($errorMessage);
            }
        }
    }
    
    /**
     * Display a summary of the reindexing process.
     *
     * @param int $duration
     * @param bool $shouldLog
     * @return void
     */
    protected function displaySummary(int $duration, bool $shouldLog): void
    {
        $this->newLine(2);
        $this->info('Search reindexing completed in ' . $duration . ' seconds');
        
        $summaryMessage = sprintf(
            "Reindexing summary: %d successful, %d failed, %d skipped",
            count($this->stats['successful']),
            count($this->stats['failed']),
            count($this->stats['skipped'])
        );
        
        $this->info($summaryMessage);
        
        if (!empty($this->stats['successful'])) {
            $this->info('Successfully processed models:');
            $this->table(['Model'], array_map(function($model) {
                return [$model];
            }, $this->stats['successful']));
        }
        
        if (!empty($this->stats['failed'])) {
            $this->error('Failed to process models:');
            $this->table(['Model'], array_map(function($model) {
                return [$model];
            }, $this->stats['failed']));
        }
        
        if ($shouldLog) {
            Log::channel('daily')->info($summaryMessage);
            Log::channel('daily')->info('Reindexing completed in ' . $duration . ' seconds');
        }
    }
    
    /**
     * Get all models using the Laravel Scout Searchable trait.
     *
     * @param string|null $specificModel
     * @return array
     */
    private function getSearchableModels(?string $specificModel = null): array
    {
        // If a specific model is requested, check and return just that one
        if ($specificModel) {
            $modelClass = "App\\Models\\{$specificModel}";
            if (class_exists($modelClass) && $this->usesSearchableTrait($modelClass)) {
                return [$modelClass];
            }
            return [];
        }
        
        $searchableModels = [];
        $modelFiles = File::files(app_path('Models'));
        
        foreach ($modelFiles as $modelFile) {
            $className = $modelFile->getFilenameWithoutExtension();
            $modelClass = "App\\Models\\{$className}";
            
            if (class_exists($modelClass) && $this->usesSearchableTrait($modelClass)) {
                $searchableModels[] = $modelClass;
            }
        }
        
        return $searchableModels;
    }
    
    /**
     * Check if a class uses the Searchable trait.
     *
     * @param string $class
     * @return bool
     */
    private function usesSearchableTrait(string $class): bool
    {
        $traits = class_uses_recursive($class);
        return isset($traits[Searchable::class]);
    }
}
