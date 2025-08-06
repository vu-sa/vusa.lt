<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * SharePoint business configuration settings.
 *
 * These are user-configurable dynamic settings stored in the database that control
 * business behavior like permission expiry and folder structure patterns.
 * These values can be changed during runtime through the admin interface.
 *
 * For static technical configuration (like API endpoints or retry logic),
 * use App\Enums\SharepointConfigEnum instead.
 *
 * @see \App\Enums\SharepointConfigEnum For static technical configuration
 */
class SharepointSettings extends Settings
{
    public int $permission_expiry_days;

    public string $default_folder_structure;

    public static function group(): string
    {
        return 'sharepoint';
    }
}
