<?php

use App\Exceptions\LimeSurveyException;
use App\Services\LimeSurveyClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::flush();

    config()->set('services.limesurvey.url', 'https://apklausos.test');
    config()->set('services.limesurvey.username', 'rpcuser');
    config()->set('services.limesurvey.password', 'secret');

    $this->client = new LimeSurveyClient;
});

/**
 * Queue the responses the client will receive, in order.
 *
 * LimeSurvey exposes every method on one endpoint, so the sequence — not the URL — is what
 * distinguishes the calls. Each test starts with the get_session_key answer.
 *
 * A bare fakeSequence() is enough precisely because there is only one endpoint; pairing it
 * with a URL-keyed Http::fake() registers the same sequence twice and consumes it twice per
 * request.
 *
 * @param  list<mixed>  $responses
 */
function fakeRpcSequence(array $responses): void
{
    $sequence = Http::fakeSequence();

    foreach ($responses as $response) {
        $sequence->pushResponse($response);
    }
}

describe('configuration', function (): void {
    test('reports itself unconfigured when credentials are missing', function (): void {
        config()->set('services.limesurvey.url', null);

        expect($this->client->isConfigured())->toBeFalse();
    });

    test('reads do not hit the network when unconfigured', function (): void {
        config()->set('services.limesurvey.url', null);
        Http::fake();

        expect($this->client->getSummary(1))->toBeNull();
        Http::assertNothingSent();
    });

    test('writes fail loudly when unconfigured', function (): void {
        config()->set('services.limesurvey.url', null);

        expect(fn () => $this->client->activateSurvey(1))->toThrow(LimeSurveyException::class);
    });

    test('derives the remotecontrol endpoint from the base url', function (): void {
        expect($this->client->endpoint())->toBe('https://apklausos.test/index.php/admin/remotecontrol');
        expect($this->client->participationUrl(42))->toBe('https://apklausos.test/index.php/42');
    });
});

describe('transport', function (): void {
    test('authenticates once and reuses the cached session key', function (): void {
        fakeRpcSequence([
            Http::response(['result' => 'session-abc', 'error' => null, 'id' => 1]),
            Http::response(['result' => ['completed_responses' => '12', 'incomplete_responses' => '3', 'full_responses' => '15'], 'error' => null, 'id' => 1]),
            Http::response(['result' => ['completed_responses' => '13', 'incomplete_responses' => '3', 'full_responses' => '16'], 'error' => null, 'id' => 1]),
        ]);

        expect($this->client->getSummary(1))->toBe(['completed' => 12, 'incomplete' => 3, 'full' => 15]);
        expect($this->client->getSummary(1))->toBe(['completed' => 13, 'incomplete' => 3, 'full' => 16]);

        // Three requests, not four: the second getSummary reused the session key.
        Http::assertSentCount(3);
    });

    test('sends a JSON-RPC v1 envelope with the session key as the first positional param', function (): void {
        fakeRpcSequence([
            Http::response(['result' => 'session-abc', 'error' => null, 'id' => 1]),
            Http::response(['result' => [], 'error' => null, 'id' => 1]),
        ]);

        $this->client->listSurveys();

        Http::assertSent(function ($request): bool {
            $body = $request->data();

            return ($body['method'] ?? null) === 'get_session_key'
                && $body['params'] === ['rpcuser', 'secret'];
        });

        Http::assertSent(function ($request): bool {
            $body = $request->data();

            return ($body['method'] ?? null) === 'list_surveys'
                && $body['params'] === ['session-abc']
                && ($body['id'] ?? null) === 1;
        });
    });

    test('re-authenticates once when the session key has expired', function (): void {
        fakeRpcSequence([
            Http::response(['result' => 'stale-key', 'error' => null, 'id' => 1]),
            Http::response(['result' => ['status' => 'Invalid session key', 'error_code' => 'ERR_INVALID_SESSION'], 'error' => null, 'id' => 1]),
            Http::response(['result' => 'fresh-key', 'error' => null, 'id' => 1]),
            Http::response(['result' => ['completed_responses' => '1'], 'error' => null, 'id' => 1]),
        ]);

        expect($this->client->getSummary(1))->toBe(['completed' => 1, 'incomplete' => 0, 'full' => 0]);

        Http::assertSentCount(4);
    });

    test('treats a status payload inside a 200 response as a failure', function (): void {
        fakeRpcSequence([
            Http::response(['result' => 'session-abc', 'error' => null, 'id' => 1]),
            Http::response(['result' => ['status' => 'No permission', 'error_code' => 'ERR_NO_PERMISSION'], 'error' => null, 'id' => 1]),
        ]);

        try {
            $this->client->activateSurvey(99);
            $this->fail('Expected a LimeSurveyException.');
        } catch (LimeSurveyException $e) {
            expect($e->errorCode)->toBe('ERR_NO_PERMISSION');
            expect($e->method)->toBe('activate_survey');
        }
    });

    test('accepts an OK status as success', function (): void {
        fakeRpcSequence([
            Http::response(['result' => 'session-abc', 'error' => null, 'id' => 1]),
            Http::response(['result' => ['status' => 'OK'], 'error' => null, 'id' => 1]),
        ]);

        $this->client->activateSurvey(99);
    })->throwsNoExceptions();

    test('surfaces a JSON-RPC error member', function (): void {
        fakeRpcSequence([
            Http::response(['result' => 'session-abc', 'error' => null, 'id' => 1]),
            Http::response(['result' => null, 'error' => 'Method not found', 'id' => 1]),
        ]);

        expect(fn () => $this->client->activateSurvey(99))
            ->toThrow(LimeSurveyException::class, 'Method not found');
    });

    test('a read degrades to null instead of throwing', function (): void {
        fakeRpcSequence([
            Http::response(['result' => 'session-abc', 'error' => null, 'id' => 1]),
            Http::response(null, 500),
        ]);

        expect($this->client->getSummary(1))->toBeNull();
    });

    test('does not cache a failed login', function (): void {
        fakeRpcSequence([
            Http::response(['result' => null, 'error' => 'Invalid user name or password', 'id' => 1]),
            Http::response(['result' => null, 'error' => 'Invalid user name or password', 'id' => 1]),
        ]);

        expect($this->client->getSummary(1))->toBeNull();
        expect(Cache::get('limesurvey.session_key'))->toBeNull();
    });
});

describe('importSurvey', function (): void {
    test('base64-encodes the document and returns the new survey id', function (): void {
        fakeRpcSequence([
            Http::response(['result' => 'session-abc', 'error' => null, 'id' => 1]),
            Http::response(['result' => 918273, 'error' => null, 'id' => 1]),
        ]);

        expect($this->client->importSurvey('<document/>'))->toBe(918273);

        Http::assertSent(function ($request): bool {
            $body = $request->data();

            if (($body['method'] ?? null) !== 'import_survey') {
                return false;
            }

            return $body['params'][1] === base64_encode('<document/>')
                && $body['params'][2] === 'lss';
        });
    });

    test('rejects a non-numeric result', function (): void {
        fakeRpcSequence([
            Http::response(['result' => 'session-abc', 'error' => null, 'id' => 1]),
            Http::response(['result' => null, 'error' => null, 'id' => 1]),
        ]);

        expect(fn () => $this->client->importSurvey('<document/>'))
            ->toThrow(LimeSurveyException::class, 'did not return a survey id');
    });
});
