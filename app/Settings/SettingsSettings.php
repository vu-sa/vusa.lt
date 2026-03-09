<?php

namespace App\Settings;

use App\Models\Role;
use Spatie\LaravelSettings\Settings;

class SettingsSettings extends Settings
{
    /**
     * The role ID that can manage settings.
     * When null, only Super Admins can manage settings.
     */
    public ?string $settings_manager_role_id = null;

    public static function group(): string
    {
        return 'settings';
    }

    /**
     * Get the role that can manage settings, or null if only Super Admin.
     */
    public function getSettingsManagerRole(): ?Role
    {
        if ($this->settings_manager_role_id === null) {
            return null;
        }

        return Role::find($this->settings_manager_role_id);
    }

    /**
     * Check if a user can manage settings.
     * Returns true if user is super admin or has the settings manager role.
     */
    public function canUserManageSettings(\App\Models\User $user): bool
    {
        // Super admins can always manage settings
        if ($user->isSuperAdmin()) {
            return true;
        }

        // If no role is set, only super admins can manage
        if ($this->settings_manager_role_id === null) {
            return false;
        }

        // Check if user has the settings manager role (directly or through duties)
        $role = $this->getSettingsManagerRole();

        if ($role === null) {
            return false;
        }

        // Check direct user role
        if ($user->hasRole($role->name)) {
            return true;
        }

        // Check roles through current duties
        foreach ($user->current_duties as $duty) {
            if ($duty->hasRole($role->name)) {
                return true;
            }
        }

        return false;
    }
}
