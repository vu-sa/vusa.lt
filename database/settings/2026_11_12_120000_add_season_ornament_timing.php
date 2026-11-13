<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('season.ornament_adjust_minutes', 0);
        $this->migrator->add('season.ornament_cooldown_hours', 0);
    }

    public function down(): void
    {
        $this->migrator->delete('season.ornament_adjust_minutes');
        $this->migrator->delete('season.ornament_cooldown_hours');
    }
};
