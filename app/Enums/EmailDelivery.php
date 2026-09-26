<?php

namespace App\Enums;

/**
 * How one notification type reaches the user by email.
 *
 * @typescript
 */
enum EmailDelivery: string
{
    case Immediate = 'immediate';
    case Digest = 'digest';
    case Off = 'off';
}
