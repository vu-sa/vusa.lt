<?php

namespace App\Jobs;

use App\Enums\SurveyStatus;
use App\Models\Survey;
use App\Services\LimeSurveyClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Pull aggregate response counters for one published survey.
 *
 * Counts only — individual responses are deliberately never copied into vusa.lt, so the
 * personal data stays in one system.
 *
 * Failures are silent by design: this only feeds a statistics panel, and LimeSurvey being
 * briefly unavailable should leave the last known numbers in place rather than blank them.
 */
class SyncSurveyStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 60;

    public function __construct(public Survey $survey)
    {
        $this->queue = 'limesurvey';
    }

    public function handle(LimeSurveyClient $client): void
    {
        if (! $this->survey->isPublished()) {
            return;
        }

        $summary = $client->getSummary($this->survey->limesurvey_survey_id);

        if ($summary === null) {
            return;
        }

        $properties = $client->getSurveyProperties($this->survey->limesurvey_survey_id, ['active', 'expires']);

        $attributes = [
            'response_stats' => $summary,
            'stats_synced_at' => now(),
        ];

        // LimeSurvey is the authority on whether the survey is still taking responses.
        if (is_array($properties) && ($properties['active'] ?? null) === 'N' && $this->survey->status === SurveyStatus::Active) {
            $attributes['status'] = SurveyStatus::Closed;
        }

        $this->survey->forceFill($attributes)->save();
    }
}
