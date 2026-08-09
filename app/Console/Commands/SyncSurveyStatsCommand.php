<?php

namespace App\Console\Commands;

use App\Jobs\SyncSurveyStatsJob;
use App\Models\Survey;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Refresh response counters for every survey that lives in LimeSurvey.
 */
#[Description('Pull response counts from LimeSurvey for published surveys')]
#[Signature('limesurvey:sync-stats
                            {--limit=100 : Maximum number of surveys to queue}
                            {--dry-run : List what would be queued without queueing it}')]
class SyncSurveyStatsCommand extends Command
{
    public function handle(): int
    {
        $surveys = Survey::query()
            ->whereNotNull('limesurvey_survey_id')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($surveys->isEmpty()) {
            $this->info('No published surveys to sync.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->table(
                ['id', 'name', 'limesurvey id', 'last synced'],
                $surveys->map(fn (Survey $s): array => [
                    $s->id,
                    $s->name,
                    $s->limesurvey_survey_id,
                    $s->stats_synced_at?->diffForHumans() ?? 'never',
                ])->all(),
            );

            return self::SUCCESS;
        }

        foreach ($surveys as $survey) {
            SyncSurveyStatsJob::dispatch($survey);
        }

        $this->info(sprintf('Queued %d survey(s).', $surveys->count()));

        return self::SUCCESS;
    }
}
