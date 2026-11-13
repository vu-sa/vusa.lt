<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('season.enabled', false);
        $this->migrator->add('season.starts_on', '12-01');
        $this->migrator->add('season.tree_locks_on', '12-25');
        $this->migrator->add('season.ends_on', '01-06');
    }

    public function down(): void
    {
        $this->migrator->delete('season.enabled');
        $this->migrator->delete('season.starts_on');
        $this->migrator->delete('season.tree_locks_on');
        $this->migrator->delete('season.ends_on');
    }
};
