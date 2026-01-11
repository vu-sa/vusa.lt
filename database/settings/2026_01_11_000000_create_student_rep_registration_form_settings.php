<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('forms.student_rep_registration_form_id', null);
        $this->migrator->add('forms.student_rep_institution_type_ids', []);
    }
};
