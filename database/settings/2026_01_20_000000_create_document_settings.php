<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('documents.important_content_types', []);
    }

    public function down(): void
    {
        $this->migrator->delete('documents.important_content_types');
    }
};
