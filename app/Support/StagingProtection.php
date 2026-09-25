<?php

namespace App\Support;

use App\Exceptions\StagingResourceReadOnlyException;

final class StagingProtection
{
    public static function filesAreReadOnly(): bool
    {
        return config('app.env') === 'staging' && (bool) config('app.files_read_only');
    }

    /**
     * In staging, a write is allowed only to an allowlisted test site and never to a production drive.
     * Omitting the site ID means the target is unknown, so it is treated as read-only.
     */
    public static function sharepointIsReadOnly(?string $siteId = null, ?string $driveId = null): bool
    {
        if (config('app.env') !== 'staging') {
            return false;
        }

        if ((bool) config('app.sharepoint_read_only')) {
            return true;
        }

        return ! in_array($siteId, config('filesystems.sharepoint.writable_site_ids', []), true)
            || in_array($driveId, config('filesystems.sharepoint.production.drive_ids', []), true);
    }

    public static function ensureFilesAreWritable(): void
    {
        if (self::filesAreReadOnly()) {
            throw StagingResourceReadOnlyException::files();
        }
    }

    public static function ensureSharepointIsWritable(?string $siteId = null, ?string $driveId = null): void
    {
        if (self::sharepointIsReadOnly($siteId, $driveId)) {
            throw StagingResourceReadOnlyException::sharepoint();
        }
    }
}
