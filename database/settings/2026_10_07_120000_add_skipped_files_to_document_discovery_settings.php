<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('document_discovery.skipped_files', 0);
    }

    public function down(): void
    {
        $this->migrator->delete('document_discovery.skipped_files');
    }
};
