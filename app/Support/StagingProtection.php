<?php

namespace App\Support;

use App\Exceptions\StagingResourceReadOnlyException;

final class StagingProtection
{
    public static function filesAreReadOnly(): bool
    {
        return config('app.env') === 'staging' && (bool) config('app.files_read_only');
    }

    public static function sharepointIsReadOnly(): bool
    {
        return config('app.env') === 'staging' && (bool) config('app.sharepoint_read_only');
    }

    public static function ensureFilesAreWritable(): void
    {
        if (self::filesAreReadOnly()) {
            throw StagingResourceReadOnlyException::files();
        }
    }

    public static function ensureSharepointIsWritable(): void
    {
        if (self::sharepointIsReadOnly()) {
            throw StagingResourceReadOnlyException::sharepoint();
        }
    }
}
