<?php

namespace App\Enums;

/**
 * How much a notification asks of its reader. It sets each NotificationType's default channels
 * (act → email now + push; know/record → digest; onboarding → in-app only) and the push TTL.
 */
enum NotificationUrgency: string
{
    /** Needs the reader to do something. */
    case Act = 'act';

    /** Worth knowing. */
    case Know = 'know';

    /** A record of something already done. */
    case Record = 'record';

    /** Warm greetings with no ask (rule 14, never send what the app can show silently). */
    case Onboarding = 'onboarding';
}
