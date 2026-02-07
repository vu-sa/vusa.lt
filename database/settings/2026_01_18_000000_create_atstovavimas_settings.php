<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Role ID that identifies institution managers (student rep coordinators)
        // who can be contacted by student representatives for help/support
        $this->migrator->add('atstovavimas.institution_manager_role_id', null);
    }

    public function down(): void
    {
        $this->migrator->delete('atstovavimas.institution_manager_role_id');
    }
};
