<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->deleteIfExists('meetings.excluded_institution_type_ids');
    }

    public function down(): void
    {
        $this->migrator->add('meetings.excluded_institution_type_ids', []);
    }
};
