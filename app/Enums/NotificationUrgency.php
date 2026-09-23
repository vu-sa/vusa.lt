<?php

namespace App\Enums;

/**
 * How much a notification asks of its reader; the channel policy (which channels send) derives
 * from this tier alone — see .ai/rules/notifications.md.
 */
enum NotificationUrgency: string
{
    /** Needs the reader to do something: email at once, push, never batched. */
    case Act = 'act';

    /** Worth knowing: batched into the email digest, in-app only otherwise. */
    case Know = 'know';

    /** A record of something already done: same routing as Know. */
    case Record = 'record';

    /** Warm greetings with no ask: in-app only (rule 14, never send what the app can show silently). */
    case Onboarding = 'onboarding';

    public function sendsImmediateMail(): bool
    {
        return $this === self::Act;
    }

    public function usesDigest(): bool
    {
        return $this === self::Know || $this === self::Record;
    }

    public function sendsPush(): bool
    {
        return $this === self::Act;
    }
}
