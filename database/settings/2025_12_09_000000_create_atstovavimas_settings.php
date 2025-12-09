<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Array of role IDs that grant tenant-wide institution visibility
        // Empty by default - must be configured by administrators
        $this->migrator->add('atstovavimas.coordinator_role_ids', []);
    }

    public function down(): void
    {
        $this->migrator->delete('atstovavimas.coordinator_role_ids');
    }
};
