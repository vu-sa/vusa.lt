<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Managing settings is the `settings.update.*` permission now (see the migration granting it).
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->deleteIfExists('settings.settings_manager_role_id');
    }

    public function down(): void
    {
        $this->migrator->add('settings.settings_manager_role_id', null);
    }
};
