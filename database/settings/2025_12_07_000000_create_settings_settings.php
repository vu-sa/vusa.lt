<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('settings.settings_manager_role_id', null);
    }

    public function down(): void
    {
        $this->migrator->delete('settings.settings_manager_role_id');
    }
};
