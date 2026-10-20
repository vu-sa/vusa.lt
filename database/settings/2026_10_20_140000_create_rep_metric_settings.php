<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('rep_metrics.baseline_cadence_id', null);
    }

    public function down(): void
    {
        $this->migrator->delete('rep_metrics.baseline_cadence_id');
    }
};
