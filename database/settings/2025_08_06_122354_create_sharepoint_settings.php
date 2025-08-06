<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('sharepoint.permission_expiry_days', 365);
        $this->migrator->add('sharepoint.default_folder_structure', 'General/{type}/{name}');
    }
};
