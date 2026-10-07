<?php

namespace App\Notifications\Contracts;

use App\Models\User;

/**
 * Opts a notification into real mail on staging, delivered only to the person whose action caused it
 * (never the recipient). Null — e.g. a scheduled send — means it is not mailed on staging at all.
 */
interface SendsMailOnStaging
{
    public function stagingMailRecipient(): ?User;
}
