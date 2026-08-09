<?php

namespace App\Exceptions;

/**
 * Raised when a LimeSurvey RemoteControl call that the user explicitly asked for fails.
 *
 * Only write operations throw. Reads (statistics, survey listings) degrade to null in
 * LimeSurveyClient, because a stale counter on a dashboard is better than a broken page —
 * but a publish that silently does nothing would be a bug, and the queued job needs a real
 * message to persist in `surveys.sync_error_message`.
 */
class LimeSurveyException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $errorCode = null,
        public readonly ?string $method = null,
    ) {
        parent::__construct($message);
    }

    /**
     * LimeSurvey 7 returns machine-readable codes such as ERR_INVALID_SESSION,
     * ERR_NO_PERMISSION, ERR_INVALID_EXTENSION or ERR_CREATION_FAILED.
     */
    public static function fromRpcError(string $method, string $status, ?string $errorCode = null): self
    {
        return new self(
            sprintf('LimeSurvey rejected %s: %s', $method, $status),
            $errorCode,
            $method,
        );
    }
}
