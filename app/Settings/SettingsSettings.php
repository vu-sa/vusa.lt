<?php

namespace App\Settings;

use App\Models\User;
use App\Services\ModelAuthorizer;
use Spatie\LaravelSettings\Settings;

/**
 * Who may change the app's settings: super admins and holders of `settings.update.*`, which a
 * responsibility carries like any other permission.
 */
class SettingsSettings extends Settings
{
    public static function group(): string
    {
        return 'settings';
    }

    public function canUserManageSettings(User $user): bool
    {
        return $user->isSuperAdmin() || app(ModelAuthorizer::class)->allows($user, 'settings.update.*');
    }
}
