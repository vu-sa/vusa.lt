<?php

use App\Enums\SurveyStatus;
use App\Exceptions\LimeSurveyException;
use App\Jobs\PublishSurveyToLimeSurveyJob;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Services\LimeSurveyClient;
use App\Services\LimeSurveyLssBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();

    config()->set('services.limesurvey.url', 'https://apklausos.test');
    config()->set('services.limesurvey.username', 'rpcuser');
    config()->set('services.limesurvey.password', 'secret');

    $this->survey = Survey::factory()->create(['status' => SurveyStatus::Approved]);
    SurveyQuestion::factory()->create(['survey_id' => $this->survey->id, 'title' => 'Q1']);
});

function runPublishJob(Survey $survey): void
{
    (new PublishSurveyToLimeSurveyJob($survey))->handle(
        app(LimeSurveyClient::class),
        app(LimeSurveyLssBuilder::class),
    );
}

/** @param  list<mixed>  $responses */
function fakeLimeSurvey(array $responses): void
{
    $sequence = Http::fakeSequence();

    foreach ($responses as $response) {
        $sequence->pushResponse($response);
    }
}

test('imports, configures and activates the survey', function (): void {
    fakeLimeSurvey([
        Http::response(['result' => 'session-abc', 'error' => null, 'id' => 1]),   // get_session_key
        Http::response(['result' => 555001, 'error' => null, 'id' => 1]),          // import_survey
        Http::response(['result' => ['status' => 'OK'], 'error' => null, 'id' => 1]), // set_survey_properties
        Http::response(['result' => ['status' => 'OK'], 'error' => null, 'id' => 1]), // activate_survey
    ]);

    runPublishJob($this->survey);

    $survey = $this->survey->fresh();

    expect($survey->limesurvey_survey_id)->toBe(555001);
    expect($survey->limesurvey_url)->toBe('https://apklausos.test/index.php/555001');
    expect($survey->status)->toBe(SurveyStatus::Active);
    expect($survey->sync_status)->toBe('synced');
    expect($survey->sync_error_message)->toBeNull();
});

test('sends the generated lss document base64-encoded', function (): void {
    fakeLimeSurvey([
        Http::response(['result' => 'session-abc', 'error' => null, 'id' => 1]),
        Http::response(['result' => 555001, 'error' => null, 'id' => 1]),
        Http::response(['result' => ['status' => 'OK'], 'error' => null, 'id' => 1]),
        Http::response(['result' => ['status' => 'OK'], 'error' => null, 'id' => 1]),
    ]);

    runPublishJob($this->survey);

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        if (($body['method'] ?? null) !== 'import_survey') {
            return false;
        }

        $xml = base64_decode($body['params'][1], true);

        return str_contains($xml, '<LimeSurveyDocType>Survey</LimeSurveyDocType>')
            && str_contains($xml, 'Q1');
    });
});

test('a survey with no questions is failed rather than pushed empty', function (): void {
    Http::fake();

    $empty = Survey::factory()->create(['status' => SurveyStatus::Approved]);

    runPublishJob($empty);

    expect($empty->fresh()->sync_status)->toBe('failed');
    expect($empty->fresh()->limesurvey_survey_id)->toBeNull();
    Http::assertNothingSent();
});

test('a retry after activation failed does not import a second survey', function (): void {
    // Both attempts are queued into one sequence: re-faking mid-test would leave the
    // exhausted first sequence registered and matching.
    fakeLimeSurvey([
        // Attempt 1 — import succeeds, activation is refused.
        Http::response(['result' => 'session-abc', 'error' => null, 'id' => 1]),
        Http::response(['result' => 555001, 'error' => null, 'id' => 1]),
        Http::response(['result' => ['status' => 'OK'], 'error' => null, 'id' => 1]),
        Http::response(['result' => ['status' => 'No permission', 'error_code' => 'ERR_NO_PERMISSION'], 'error' => null, 'id' => 1]),
        // Attempt 2 — the session key is cached, so it resumes at set_survey_properties.
        Http::response(['result' => ['status' => 'OK'], 'error' => null, 'id' => 1]),
        Http::response(['result' => ['status' => 'OK'], 'error' => null, 'id' => 1]),
    ]);

    expect(fn () => runPublishJob($this->survey))->toThrow(LimeSurveyException::class);

    // The id is already persisted, so the retry must resume rather than re-import.
    expect($this->survey->fresh()->limesurvey_survey_id)->toBe(555001);

    runPublishJob($this->survey->fresh());

    expect($this->survey->fresh()->status)->toBe(SurveyStatus::Active);
    expect(Survey::query()->where('limesurvey_survey_id', 555001)->count())->toBe(1);

    $imports = Http::recorded(fn ($request): bool => ($request->data()['method'] ?? null) === 'import_survey');

    expect($imports)->toHaveCount(1);
});

test('records the LimeSurvey error message for the admin to see', function (): void {
    fakeLimeSurvey([
        Http::response(['result' => 'session-abc', 'error' => null, 'id' => 1]),
        Http::response(['result' => ['status' => 'Creation failed', 'error_code' => 'ERR_CREATION_FAILED'], 'error' => null, 'id' => 1]),
    ]);

    expect(fn () => runPublishJob($this->survey))->toThrow(LimeSurveyException::class);

    expect($this->survey->fresh()->sync_error_message)->toContain('Creation failed');
    expect($this->survey->fresh()->status)->toBe(SurveyStatus::Approved);
});

test('does nothing when LimeSurvey is not configured', function (): void {
    config()->set('services.limesurvey.url', null);
    Http::fake();

    runPublishJob($this->survey);

    expect($this->survey->fresh()->sync_status)->toBe('failed');
    Http::assertNothingSent();
});
