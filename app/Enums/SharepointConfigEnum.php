<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * SharePoint technical configuration constants.
 *
 * These are hardcoded static values for technical configuration like API endpoints,
 * retry logic, and timeouts. These values should not be changed during runtime.
 *
 * For user-configurable business settings (like permission expiry days or folder structure),
 * use App\Settings\SharepointSettings instead.
 *
 * @see \App\Settings\SharepointSettings For dynamic business configuration
 *
 * @typescript
 *
 * @method static self API_BASE_URL()
 * @method static self DEFAULT_TIMEOUT()
 * @method static self MAX_RETRIES()
 * @method static self RETRY_DELAY_MS()
 * @method static self DEFAULT_BATCH_SIZE()
 */
final class SharepointConfigEnum extends Enum
{
    protected static function labels(): array
    {
        return [
            'API_BASE_URL' => 'https://graph.microsoft.com/v1.0/',
            'DEFAULT_TIMEOUT' => '30',
            'MAX_RETRIES' => '3',
            'RETRY_DELAY_MS' => '1000',
            'DEFAULT_BATCH_SIZE' => '20',
        ];
    }
}
