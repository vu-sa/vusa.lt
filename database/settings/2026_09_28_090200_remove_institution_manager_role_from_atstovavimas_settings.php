<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Coordinators are duty responsibilities now (see the backfill migration of the same day).
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->deleteIfExists('atstovavimas.institution_manager_role_id');
    }

    public function down(): void
    {
        $this->migrator->add('atstovavimas.institution_manager_role_id', null);
    }
};
