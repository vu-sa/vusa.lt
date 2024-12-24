<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('forms.member_registration_form_id', null);
        $this->migrator->add('forms.member_registration_notification_recipient_role_id', null);
    }
};
