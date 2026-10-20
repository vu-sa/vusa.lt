<?php

namespace App\Settings;

use App\Models\User;
use App\Services\ModelAuthorizer;
use Spatie\LaravelSettings\Settings;

/**
 * Who may open the app's settings (`settings.read.*`) and who may change them (`settings.update.*`):
 * super admins and holders of those permissions, which a responsibility carries like any other.
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

    public function canUserViewSettings(User $user): bool
    {
        return $this->canUserManageSettings($user) || app(ModelAuthorizer::class)->allows($user, 'settings.read.*');
    }
}
