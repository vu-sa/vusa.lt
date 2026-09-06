<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('atstovavimas.student_rep_root_type_id', null);
    }

    public function down(): void
    {
        $this->migrator->delete('atstovavimas.student_rep_root_type_id');
    }
};
