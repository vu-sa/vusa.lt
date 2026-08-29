<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('cadence.default_start_month_day', '07-01');
        $this->migrator->add('cadence.default_end_month_day', '06-30');
    }

    public function down(): void
    {
        $this->migrator->delete('cadence.default_start_month_day');
        $this->migrator->delete('cadence.default_end_month_day');
    }
};
