<?php

namespace App\Exceptions;

/**
 * SharePoint asked for a pause longer than a run should sleep through; nothing may call it again before then.
 */
class SharepointThrottledException extends \RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct("SharePoint throttled requests for {$retryAfterSeconds} seconds");
    }
}
