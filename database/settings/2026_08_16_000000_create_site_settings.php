<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('site.privacy_page_id', null);
    }

    public function down(): void
    {
        $this->migrator->delete('site.privacy_page_id');
    }
};
