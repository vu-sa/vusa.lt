<?php

namespace App\Jobs;

use App\Enums\SurveyStatus;
use App\Exceptions\LimeSurveyException;
use App\Models\Survey;
use App\Services\LimeSurveyClient;
use App\Services\LimeSurveyLssBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Push an approved survey to LimeSurvey and take it live.
 *
 * Runs on a queue rather than inline because a slow or unreachable LimeSurvey must not
 * block the approver's request, and because the import is worth retrying.
 *
 * Retry safety matters more than usual here: import_survey creates a new survey every time
 * it succeeds, so a retry after a *later* step failed must not import a second copy. The
 * survey id is therefore persisted the instant it is known, and handle() resumes from
 * whatever stage the record shows.
 */
class PublishSurveyToLimeSurveyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    public function __construct(public Survey $survey)
    {
        $this->queue = 'limesurvey';
    }

    public function handle(LimeSurveyClient $client, LimeSurveyLssBuilder $builder): void
    {
        if (! $client->isConfigured()) {
            $this->markFailed('LimeSurvey is not configured.');

            return;
        }

        if ($this->survey->status === SurveyStatus::Active) {
            return;
        }

        if ($this->survey->questions()->count() === 0) {
            $this->markFailed('The survey has no questions.');

            return;
        }

        $this->survey->forceFill(['sync_status' => 'syncing'])->save();

        try {
            $surveyId = $this->survey->limesurvey_survey_id ?? $this->import($client, $builder);

            $client->setSurveyProperties($surveyId, array_filter([
                'startdate' => $this->survey->starts_at?->format('Y-m-d H:i:s'),
                'expires' => $this->survey->ends_at?->format('Y-m-d H:i:s'),
                'anonymized' => $this->survey->is_anonymous ? 'Y' : 'N',
            ]));

            $client->activateSurvey($surveyId);

            $this->survey->forceFill([
                'status' => SurveyStatus::Active,
                'sync_status' => 'synced',
                'sync_error_message' => null,
                'limesurvey_url' => $client->participationUrl($surveyId),
            ])->save();

            Log::info('Survey published to LimeSurvey', [
                'survey_id' => $this->survey->id,
                'limesurvey_survey_id' => $surveyId,
            ]);
        } catch (LimeSurveyException $e) {
            // Rethrow so the queue retries; failed() records the terminal state.
            $this->survey->forceFill(['sync_error_message' => $e->getMessage()])->save();

            throw $e;
        }
    }

    /**
     * Create the survey in LimeSurvey, persisting the id before anything else can fail.
     *
     * @throws LimeSurveyException
     */
    private function import(LimeSurveyClient $client, LimeSurveyLssBuilder $builder): int
    {
        $this->survey->loadMissing('questions');

        $surveyId = $client->importSurvey($builder->build($this->survey));

        $this->survey->forceFill(['limesurvey_survey_id' => $surveyId])->save();

        return $surveyId;
    }

    private function markFailed(string $message): void
    {
        $this->survey->forceFill([
            'sync_status' => 'failed',
            'sync_error_message' => $message,
        ])->save();

        Log::warning('Survey publish aborted', [
            'survey_id' => $this->survey->id,
            'reason' => $message,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->survey->forceFill([
            'sync_status' => 'failed',
            'sync_error_message' => 'Job failed after '.$this->tries.' attempts: '.$exception->getMessage(),
        ])->save();
    }
}
