<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * SharePoint technical configuration constants.
 *
 * These are hardcoded static values for technical configuration like API endpoints,
 * retry logic, and timeouts. These values should not be changed during runtime.
 *
 * For user-configurable business settings (like permission expiry days), these are
 * now hardcoded as constants in the SharepointGraphService class.
 */
enum SharepointConfigEnum: string
{
    use HasEnumHelpers;

    case API_BASE_URL = 'API_BASE_URL';
    case DEFAULT_TIMEOUT = 'DEFAULT_TIMEOUT';
    case MAX_RETRIES = 'MAX_RETRIES';
    case RETRY_DELAY_MS = 'RETRY_DELAY_MS';
    case DEFAULT_BATCH_SIZE = 'DEFAULT_BATCH_SIZE';

    public function label(): string
    {
        return match ($this) {
            self::API_BASE_URL => 'https://graph.microsoft.com/v1.0/',
            self::DEFAULT_TIMEOUT => '30',
            self::MAX_RETRIES => '3',
            self::RETRY_DELAY_MS => '1000',
            self::DEFAULT_BATCH_SIZE => '20',
        };
    }
}
