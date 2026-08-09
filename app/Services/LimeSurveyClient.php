<?php

namespace App\Services;

use App\Exceptions\LimeSurveyException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client for the LimeSurvey 7 RemoteControl 2 interface (JSON-RPC v1).
 *
 * Written against Laravel's Http facade rather than the jsonrpcphp package the LimeSurvey
 * manual suggests: that package talks raw sockets, which cannot be faked in tests, and it
 * would be a new dependency for roughly eighty lines of code.
 *
 * Two deliberate behaviours:
 * - Reads (listSurveys, getSummary, getSurveyProperties) log and return null on failure.
 *   A statistics panel degrading to an empty state must never break an admin page.
 * - Writes (importSurvey, setSurveyProperties, activateSurvey) throw LimeSurveyException.
 *   These only run from a queued job the user triggered; failing silently would leave a
 *   survey that looks published but does not exist.
 *
 * Protocol notes, all of which are easy to get wrong:
 * - JSON-RPC **v1**: `params` is a positional array, not an object. Argument order matters.
 * - The Content-Type must be application/json; LimeSurvey answers with a 200 and an `error`
 *   member rather than an HTTP error status, so `$response->failed()` is not enough.
 * - Several methods signal failure inside a successful `result` — an array shaped
 *   `{status: "...", error_code: "..."}` instead of the value. That is checked centrally.
 * - The session key expires server-side. On ERR_INVALID_SESSION the cached key is dropped
 *   and the call retried exactly once.
 */
class LimeSurveyClient
{
    private const string SESSION_CACHE_KEY = 'limesurvey.session_key';

    /** Comfortably under LimeSurvey's own session lifetime, so a cached key is not served past expiry. */
    private const SESSION_TTL = 60 * 30;

    private const int TIMEOUT = 30;

    /**
     * Whether the integration is configured at all. Staging and CI leave it empty.
     */
    public function isConfigured(): bool
    {
        return filled(config('services.limesurvey.url'))
            && filled(config('services.limesurvey.username'))
            && filled(config('services.limesurvey.password'));
    }

    /**
     * The RemoteControl endpoint, derived from the configured base URL.
     */
    public function endpoint(): string
    {
        return rtrim((string) config('services.limesurvey.url'), '/').'/index.php/admin/remotecontrol';
    }

    /**
     * The public participation URL for a survey.
     */
    public function participationUrl(int $surveyId): string
    {
        return rtrim((string) config('services.limesurvey.url'), '/').'/index.php/'.$surveyId;
    }

    /**
     * Surveys visible to the RPC user. Null when unreachable.
     *
     * @return list<array<string, mixed>>|null
     */
    public function listSurveys(): ?array
    {
        return $this->read('list_surveys', []);
    }

    /**
     * Response counters for one survey.
     *
     * LimeSurvey returns every value as a string; they are normalised to ints here so
     * callers and the frontend never have to care.
     *
     * @return array{completed: int, incomplete: int, full: int}|null
     */
    public function getSummary(int $surveyId): ?array
    {
        $summary = $this->read('get_summary', [$surveyId, 'all']);

        if (! is_array($summary)) {
            return null;
        }

        return [
            'completed' => (int) ($summary['completed_responses'] ?? 0),
            'incomplete' => (int) ($summary['incomplete_responses'] ?? 0),
            'full' => (int) ($summary['full_responses'] ?? 0),
        ];
    }

    /**
     * Selected survey properties, e.g. ['active', 'expires', 'startdate'].
     *
     * @param  list<string>  $properties
     * @return array<string, mixed>|null
     */
    public function getSurveyProperties(int $surveyId, array $properties): ?array
    {
        $result = $this->read('get_survey_properties', [$surveyId, $properties]);

        return is_array($result) ? $result : null;
    }

    /**
     * Import a complete survey from an .lss document and return the new LimeSurvey id.
     *
     * The XML goes over the wire base64-encoded — LimeSurvey decodes it with
     * base64_decode(chunk_split(...)) on the other side.
     *
     * @throws LimeSurveyException
     */
    public function importSurvey(string $lssXml, ?string $newTitle = null): int
    {
        $surveyId = $this->write('import_survey', [
            base64_encode($lssXml),
            'lss',
            $newTitle,
        ]);

        if (! is_numeric($surveyId)) {
            throw new LimeSurveyException('LimeSurvey did not return a survey id from import_survey.');
        }

        return (int) $surveyId;
    }

    /**
     * Update survey-level settings such as start/expiry dates and anonymity.
     *
     * @param  array<string, mixed>  $properties
     *
     * @throws LimeSurveyException
     */
    public function setSurveyProperties(int $surveyId, array $properties): void
    {
        $this->write('set_survey_properties', [$surveyId, $properties]);
    }

    /**
     * Take a survey live.
     *
     * Irreversible in practice: LimeSurvey locks the question structure of an active
     * survey, and deactivating archives the responses collected so far.
     *
     * @throws LimeSurveyException
     */
    public function activateSurvey(int $surveyId): void
    {
        $this->write('activate_survey', [$surveyId]);
    }

    /**
     * Delete a survey. Only used to clean up after a failed publish.
     *
     * @throws LimeSurveyException
     */
    public function deleteSurvey(int $surveyId): void
    {
        $this->write('delete_survey', [$surveyId]);
    }

    // =========================================================================
    // Transport
    // =========================================================================

    /**
     * A call whose failure is tolerable: logged, and reported as null.
     *
     * @param  list<mixed>  $params
     */
    private function read(string $method, array $params): mixed
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            return $this->call($method, $params);
        } catch (\Throwable $e) {
            Log::warning('LimeSurvey read failed', ['method' => $method, 'message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * A call whose failure the caller must hear about.
     *
     * @param  list<mixed>  $params
     *
     * @throws LimeSurveyException
     */
    private function write(string $method, array $params): mixed
    {
        if (! $this->isConfigured()) {
            throw new LimeSurveyException('LimeSurvey is not configured.', method: $method);
        }

        return $this->call($method, $params);
    }

    /**
     * Issue one RPC call, re-authenticating once if the session key has expired.
     *
     * @param  list<mixed>  $params
     *
     * @throws LimeSurveyException
     */
    private function call(string $method, array $params): mixed
    {
        $result = $this->dispatch($method, array_merge([$this->sessionKey()], $params));

        if ($this->isExpiredSession($result)) {
            Cache::forget(self::SESSION_CACHE_KEY);
            $result = $this->dispatch($method, array_merge([$this->sessionKey()], $params));
        }

        return $this->unwrap($method, $result);
    }

    /**
     * Send the JSON-RPC envelope and return the raw `result` member.
     *
     * Params are positional and passed through verbatim — the session key, where one is
     * needed, is prepended by the caller.
     *
     * @param  list<mixed>  $params
     *
     * @throws LimeSurveyException
     */
    private function dispatch(string $method, array $params): mixed
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->asJson()
                ->acceptJson()
                ->post($this->endpoint(), [
                    'method' => $method,
                    'params' => array_values($params),
                    'id' => 1,
                ]);
        } catch (\Throwable $e) {
            throw new LimeSurveyException(
                sprintf('Could not reach LimeSurvey (%s): %s', $method, $e->getMessage()),
                method: $method,
            );
        }

        if ($response->failed()) {
            throw new LimeSurveyException(
                sprintf('LimeSurvey returned HTTP %d for %s.', $response->status(), $method),
                method: $method,
            );
        }

        $body = $response->json();

        if (! is_array($body)) {
            throw new LimeSurveyException(sprintf('LimeSurvey returned a non-JSON body for %s.', $method), method: $method);
        }

        if (filled($body['error'] ?? null)) {
            throw new LimeSurveyException(
                sprintf('LimeSurvey error on %s: %s', $method, is_string($body['error']) ? $body['error'] : json_encode($body['error'])),
                method: $method,
            );
        }

        return $body['result'] ?? null;
    }

    /**
     * LimeSurvey reports most failures inside a 200 response, as a `status` array.
     *
     * @throws LimeSurveyException
     */
    private function unwrap(string $method, mixed $result): mixed
    {
        if (is_array($result) && isset($result['status']) && is_string($result['status'])) {
            $errorCode = isset($result['error_code']) && is_string($result['error_code'])
                ? $result['error_code']
                : null;

            // "OK" is a success payload for methods that return nothing meaningful.
            if (strcasecmp($result['status'], 'OK') !== 0) {
                throw LimeSurveyException::fromRpcError($method, $result['status'], $errorCode);
            }
        }

        return $result;
    }

    private function isExpiredSession(mixed $result): bool
    {
        if (! is_array($result)) {
            return false;
        }

        $status = $result['status'] ?? null;
        $code = $result['error_code'] ?? null;

        return $code === 'ERR_INVALID_SESSION'
            || (is_string($status) && str_contains(strtolower($status), 'invalid session key'));
    }

    /**
     * A cached session key, or a freshly minted one.
     *
     * A failed login is never cached — the next request should try again rather than be
     * locked out for the whole TTL.
     *
     * @throws LimeSurveyException
     */
    private function sessionKey(): string
    {
        $key = Cache::get(self::SESSION_CACHE_KEY);

        if (is_string($key) && $key !== '') {
            return $key;
        }

        // get_session_key is the one method that takes credentials instead of a session key.
        $key = $this->dispatch('get_session_key', [
            (string) config('services.limesurvey.username'),
            (string) config('services.limesurvey.password'),
        ]);

        if (! is_string($key) || $key === '') {
            throw new LimeSurveyException('LimeSurvey refused the RPC credentials.', method: 'get_session_key');
        }

        Cache::put(self::SESSION_CACHE_KEY, $key, self::SESSION_TTL);

        return $key;
    }
}
