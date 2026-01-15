<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('atstovavimas.global_visibility_role_ids')) {
            $this->migrator->add('atstovavimas.global_visibility_role_ids', []);
        }

        if ($this->migrator->exists('atstovavimas.coordinator_role_ids')
            && ! $this->migrator->exists('atstovavimas.tenant_visibility_role_ids')) {
            $this->migrator->rename('atstovavimas.coordinator_role_ids', 'atstovavimas.tenant_visibility_role_ids');
        }

        if (! $this->migrator->exists('atstovavimas.tenant_visibility_role_ids')) {
            $this->migrator->add('atstovavimas.tenant_visibility_role_ids', []);
        }

        $this->migrator->deleteIfExists('atstovavimas.coordinator_role_ids');
    }

    public function down(): void
    {
        $this->migrator->delete('atstovavimas.global_visibility_role_ids');
        $this->migrator->delete('atstovavimas.tenant_visibility_role_ids');
    }
};
