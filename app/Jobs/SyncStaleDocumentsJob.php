<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncStaleDocumentsJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 1;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public $timeout = 600; // 10 minutes for bulk operation

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // Set queue name for better organization
        $this->queue = 'sharepoint-sync';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting scheduled rolling document refresh (14-day cycle)');

        // Get documents for refresh using rolling strategy
        $documentsToRefresh = $this->getDocumentsForRefresh();

        if ($documentsToRefresh->isEmpty()) {
            Log::info('No documents selected for refresh cycle');

            return;
        }

        // Log age distribution for monitoring
        $ageStats = $this->getDocumentAgeStatistics($documentsToRefresh);
        Log::info('Selected documents for 14-day rolling refresh', [
            'count' => $documentsToRefresh->count(),
            'age_distribution' => $ageStats,
        ]);

        $successCount = 0;
        $skippedCount = 0;

        // Process documents in batches to avoid overwhelming SharePoint API
        $documentsToRefresh->chunk(10)->each(function ($batch) use (&$successCount, &$skippedCount) {
            foreach ($batch as $document) {
                // Skip documents that have failed too many times recently
                if ($this->shouldSkipDocument($document)) {
                    $skippedCount++;
                    Log::debug('Skipping document due to recent failures', [
                        'document_id' => $document->id,
                        'sync_attempts' => $document->sync_attempts,
                    ]);

                    continue;
                }

                // Dispatch individual sync job
                SyncDocumentFromSharePointJob::dispatch($document);
                $successCount++;

                // Small delay to be respectful to SharePoint API
                usleep(250000); // 250ms delay between dispatches
            }

            // Longer delay between batches
            if (! $batch->isEmpty()) {
                sleep(2); // 2 second delay between batches
            }
        });

        Log::info('Completed rolling document refresh dispatch', [
            'dispatched' => $successCount,
            'skipped' => $skippedCount,
            'total_selected' => $documentsToRefresh->count(),
            'refresh_cycle' => '14-day rolling strategy',
        ]);
    }

    /**
     * Get documents for refresh using rolling 14-day cycle strategy
     */
    private function getDocumentsForRefresh()
    {
        $dailyQuota = $this->calculateDynamicDailyQuota();

        Log::info('Calculated daily refresh quota', [
            'daily_quota' => $dailyQuota,
            'total_documents' => Document::count(),
        ]);

        // Priority 1: Documents older than 14 days (critical refresh needed)
        $criticalDocs = Document::query()
            ->where(function ($query) {
                $query->whereNull('checked_at')
                    ->orWhere('checked_at', '<', now()->subDays(14));
            })
            ->where('sync_status', '!=', 'syncing')
            // Prioritize active and public documents within critical group
            ->orderByRaw('
                CASE 
                    WHEN is_active = 1 AND anonymous_url IS NOT NULL THEN 1
                    WHEN is_active = 1 THEN 2
                    WHEN anonymous_url IS NOT NULL THEN 3
                    ELSE 4
                END
            ')
            ->orderBy('checked_at', 'asc') // Oldest first within each priority
            ->limit($dailyQuota)
            ->get();

        $remaining = $dailyQuota - $criticalDocs->count();

        // Priority 2: Fill remaining quota with documents from 7-14 day range (random selection)
        if ($remaining > 0) {
            $additionalDocs = Document::query()
                ->whereBetween('checked_at', [now()->subDays(14), now()->subDays(7)])
                ->where('sync_status', '!=', 'syncing')
                ->inRandomOrder()
                ->limit($remaining)
                ->get();

            Log::info('Filled remaining quota with additional documents', [
                'critical_docs' => $criticalDocs->count(),
                'additional_docs' => $additionalDocs->count(),
                'total_selected' => $criticalDocs->count() + $additionalDocs->count(),
            ]);

            return $criticalDocs->concat($additionalDocs);
        }

        Log::info('Daily quota filled entirely with critical documents', [
            'critical_docs' => $criticalDocs->count(),
        ]);

        return $criticalDocs;
    }

    /**
     * Calculate dynamic daily quota for 14-day rolling refresh cycle
     */
    private function calculateDynamicDailyQuota(): int
    {
        $totalDocs = Document::count();
        $refreshCycleDays = 14; // Could be moved to config in future

        // Calculate base quota (documents per day to refresh all docs in 14 days)
        $baseQuota = max(1, ceil($totalDocs / $refreshCycleDays));

        // Add randomization to prevent predictable patterns (±10%)
        $minQuota = max(1, (int) ($baseQuota * 0.9));
        $maxQuota = (int) ($baseQuota * 1.1);

        $dynamicQuota = random_int($minQuota, $maxQuota);

        Log::debug('Daily quota calculation', [
            'total_documents' => $totalDocs,
            'refresh_cycle_days' => $refreshCycleDays,
            'base_quota' => $baseQuota,
            'min_quota' => $minQuota,
            'max_quota' => $maxQuota,
            'selected_quota' => $dynamicQuota,
        ]);

        return $dynamicQuota;
    }

    /**
     * Get document age distribution statistics for monitoring
     */
    private function getDocumentAgeStatistics($documents): array
    {
        $now = now();
        $ageGroups = [
            'never_checked' => 0,
            '0_7_days' => 0,
            '7_14_days' => 0,
            '14_30_days' => 0,
            'over_30_days' => 0,
        ];

        foreach ($documents as $document) {
            if (! $document->checked_at) {
                $ageGroups['never_checked']++;

                continue;
            }

            $daysSinceCheck = $now->diffInDays($document->checked_at);

            if ($daysSinceCheck <= 7) {
                $ageGroups['0_7_days']++;
            } elseif ($daysSinceCheck <= 14) {
                $ageGroups['7_14_days']++;
            } elseif ($daysSinceCheck <= 30) {
                $ageGroups['14_30_days']++;
            } else {
                $ageGroups['over_30_days']++;
            }
        }

        return $ageGroups;
    }

    /**
     * Determine if a document should be skipped due to recent failures
     */
    private function shouldSkipDocument(Document $document): bool
    {
        // Skip if it has failed more than 5 times
        if ($document->sync_attempts > 5) {
            return true;
        }

        // Skip if it failed recently and has multiple attempts
        if ($document->sync_status === 'failed' &&
            $document->sync_attempts >= 3 &&
            $document->last_sync_attempt_at &&
            $document->last_sync_attempt_at > now()->subHours(6)) {
            return true;
        }

        return false;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Rolling document refresh job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'refresh_strategy' => '14-day rolling cycle',
        ]);
    }
}
