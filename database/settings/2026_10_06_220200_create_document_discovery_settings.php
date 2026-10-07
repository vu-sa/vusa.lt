<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * SharePoint discovery's own checkpoint. A separate group from DocumentSettings, because Spatie
 * saves every property of a group: a long scan must not overwrite recommendations edited meanwhile.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('document_discovery.delta_link', null);
        $this->migrator->add('document_discovery.last_run_at', null);
        $this->migrator->add('document_discovery.last_full_run_at', null);
    }

    public function down(): void
    {
        $this->migrator->delete('document_discovery.delta_link');
        $this->migrator->delete('document_discovery.last_run_at');
        $this->migrator->delete('document_discovery.last_full_run_at');
    }
};
