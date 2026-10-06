<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('documents.recommendations', []);
    }

    public function down(): void
    {
        $this->migrator->delete('documents.recommendations');
    }
};
