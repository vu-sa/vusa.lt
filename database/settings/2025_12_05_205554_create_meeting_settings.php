<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('meetings.public_meeting_institution_type_ids', []);
    }

    public function down(): void
    {
        $this->migrator->delete('meetings.public_meeting_institution_type_ids');
    }
};
