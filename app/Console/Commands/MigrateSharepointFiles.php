<?php

namespace App\Console\Commands;

use App\Models\FileableFile;
use App\Models\Pivots\SharepointFileable;
use App\Services\ResourceServices\SharepointFileService;
use App\Services\SharepointGraphService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateSharepointFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sharepoint:migrate-files 
                            {--dry-run : Show what would be migrated without making changes}
                            {--cleanup : Delete legacy sharepoint_fileables records after successful migration}
                            {--model= : Only migrate files for a specific model type (Meeting, Institution, Type)}
                            {--limit= : Limit the number of files to migrate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing SharePoint files from legacy pivot table to new FileableFile records with local metadata';

    protected SharepointGraphService $sharepointService;

    protected bool $dryRun = false;

    protected int $successCount = 0;

    protected int $errorCount = 0;

    protected int $skippedCount = 0;

    protected int $cleanedUpCount = 0;

    /** @var array<int, int> Pivot IDs successfully migrated (for cleanup) */
    protected array $migratedPivotIds = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->dryRun = $this->option('dry-run');
        $modelFilter = $this->option('model');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if ($this->dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made, no SharePoint API calls');
        }

        $this->info('Starting SharePoint file migration...');
        $this->newLine();

        // Initialize SharePoint service only when not in dry-run mode
        if (! $this->dryRun) {
            try {
                $this->sharepointService = new SharepointGraphService(
                    driveId: config('filesystems.sharepoint.vusa_drive_id')
                );
            } catch (\Exception $e) {
                $this->error('Failed to initialize SharePoint service: '.$e->getMessage());

                return 1;
            }
        }

        // Get all sharepoint_fileables records
        $query = SharepointFileable::with(['sharepointFile']);

        if ($modelFilter) {
            $modelClass = 'App\\Models\\'.$modelFilter;
            if (class_exists($modelClass)) {
                $query->where('fileable_type', $modelClass);
            } else {
                $this->error("Invalid model type: {$modelFilter}");

                return 1;
            }
        }

        if ($limit) {
            $query->limit($limit);
        }

        $fileables = $query->get();
        $total = $fileables->count();

        $this->info("Found {$total} file associations to migrate");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        foreach ($fileables as $fileable) {
            try {
                $this->migrateFileable($fileable);
            } catch (\Exception $e) {
                $this->errorCount++;
                Log::error('SharePoint migration error', [
                    'fileable_id' => $fileable->fileable_id,
                    'fileable_type' => $fileable->fileable_type,
                    'error' => $e->getMessage(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Cleanup legacy records if requested
        if ($this->option('cleanup') && ! $this->dryRun && count($this->migratedPivotIds) > 0) {
            $this->info('Cleaning up legacy records...');
            $this->cleanedUpCount = $this->cleanupLegacyRecords();
        }

        // Summary
        $this->info('Migration Complete!');

        $summaryRows = [
            ['✅ Migrated', $this->successCount],
            ['⏭️ Skipped (already exists)', $this->skippedCount],
            ['❌ Errors', $this->errorCount],
        ];

        if ($this->option('cleanup')) {
            $summaryRows[] = ['🗑️ Legacy records deleted', $this->cleanedUpCount];
        }

        $this->table(
            ['Status', 'Count'],
            $summaryRows
        );

        return 0;
    }

    protected function migrateFileable(SharepointFileable $pivot): void
    {
        $sharepointFile = $pivot->sharepointFile;

        if (! $sharepointFile) {
            $this->errorCount++;

            return;
        }

        // Check if already migrated
        $existingFileableFile = FileableFile::where('sharepoint_id', $sharepointFile->sharepoint_id)
            ->where('fileable_type', $pivot->fileable_type)
            ->where('fileable_id', $pivot->fileable_id)
            ->first();

        if ($existingFileableFile) {
            $this->skippedCount++;

            return;
        }

        // Get the fileable model
        $fileableClass = $pivot->fileable_type;

        // Check if the class exists (some old records may reference deleted models)
        if (! class_exists($fileableClass)) {
            Log::warning('Skipping migration: fileable class does not exist', [
                'fileable_type' => $fileableClass,
                'fileable_id' => $pivot->fileable_id,
            ]);
            $this->errorCount++;

            return;
        }

        $fileable = $fileableClass::find($pivot->fileable_id);

        if (! $fileable) {
            $this->errorCount++;

            return;
        }

        // Check if the model implements SharepointFileableContract
        if (! in_array(\App\Models\Traits\HasSharepointFiles::class, class_uses_recursive($fileable))) {
            Log::warning('Skipping migration: fileable does not have HasSharepointFiles trait', [
                'fileable_type' => $fileableClass,
                'fileable_id' => $pivot->fileable_id,
            ]);
            $this->errorCount++;

            return;
        }

        // In dry-run mode, just count valid records without making API calls
        if ($this->dryRun) {
            $this->successCount++;

            return;
        }

        // Fetch metadata from SharePoint
        $metadata = $this->fetchSharepointMetadata($sharepointFile->sharepoint_id);

        if (! $metadata) {
            // File might have been deleted in SharePoint
            Log::warning('Skipping migration: could not fetch SharePoint metadata', [
                'sharepoint_id' => $sharepointFile->sharepoint_id,
                'fileable_type' => $pivot->fileable_type,
                'fileable_id' => $pivot->fileable_id,
            ]);
            $this->errorCount++;

            return;
        }

        // Create FileableFile record
        try {
            DB::transaction(function () use ($pivot, $sharepointFile, $fileable, $metadata) {
                $canonicalPath = SharepointFileService::pathForFileableDriveItem($fileable);

                FileableFile::create([
                    'fileable_type' => $pivot->fileable_type,
                    'fileable_id' => $pivot->fileable_id,
                    'sharepoint_id' => $sharepointFile->sharepoint_id,
                    'sharepoint_path' => $canonicalPath.'/'.($metadata['name'] ?? 'unknown'),
                    'name' => $metadata['name'] ?? 'Unknown file',
                    'file_type' => $metadata['file_type'] ?? null,
                    'mime_type' => $metadata['mime_type'] ?? null,
                    'size_bytes' => $metadata['size'] ?? null,
                    'file_date' => $metadata['date'] ?? null,
                    'description' => $metadata['description'] ?? null,
                    'last_synced_at' => now(),
                ]);
            });

            $this->successCount++;
            $this->migratedPivotIds[] = $pivot->id;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // pathForFileableDriveItem uses abort() for missing relationships
            Log::warning('Skipping migration: failed to generate path', [
                'fileable_type' => $pivot->fileable_type,
                'fileable_id' => $pivot->fileable_id,
                'error' => $e->getMessage(),
            ]);
            $this->errorCount++;
        }
    }

    protected function fetchSharepointMetadata(string $sharepointId): ?array
    {
        try {
            $driveItem = $this->sharepointService->getDriveItemById($sharepointId);

            if (! $driveItem) {
                return null;
            }

            $listItem = $driveItem->getListItem();
            $fields = $listItem?->getFields()?->getAdditionalData() ?? [];

            return [
                'name' => $driveItem->getName(),
                'size' => $driveItem->getSize(),
                'mime_type' => $driveItem->getFile()?->getMimeType(),
                'file_type' => $fields['Type'] ?? null,
                'date' => isset($fields['Date']) ? \Carbon\Carbon::parse($fields['Date']) : null,
                'description' => $fields['Description0'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to fetch SharePoint metadata', [
                'sharepoint_id' => $sharepointId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Delete legacy sharepoint_fileables records that were successfully migrated.
     * Also cleans up orphaned sharepoint_files records.
     */
    protected function cleanupLegacyRecords(): int
    {
        $deletedCount = 0;

        // Delete migrated pivot records
        $deletedCount = SharepointFileable::whereIn('id', $this->migratedPivotIds)->delete();

        // Clean up orphaned sharepoint_files (files not referenced by any pivot)
        $orphanedFiles = DB::table('sharepoint_files')
            ->leftJoin('sharepoint_fileables', 'sharepoint_files.id', '=', 'sharepoint_fileables.sharepoint_file_id')
            ->whereNull('sharepoint_fileables.id')
            ->pluck('sharepoint_files.id');

        if ($orphanedFiles->isNotEmpty()) {
            $orphanedCount = DB::table('sharepoint_files')
                ->whereIn('id', $orphanedFiles)
                ->delete();

            Log::info('Cleaned up orphaned sharepoint_files', ['count' => $orphanedCount]);
        }

        return $deletedCount;
    }
}
