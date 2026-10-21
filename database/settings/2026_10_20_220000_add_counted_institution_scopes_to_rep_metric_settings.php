<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('rep_metrics.counted_institution_scopes', ['vu']);
    }

    public function down(): void
    {
        $this->migrator->delete('rep_metrics.counted_institution_scopes');
    }
};
