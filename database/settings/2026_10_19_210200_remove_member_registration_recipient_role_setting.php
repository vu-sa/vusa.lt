<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Registration recipients hold the member registration responsibility now (see the grants backfill).
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->deleteIfExists('forms.member_registration_notification_recipient_role_id');
    }

    public function down(): void
    {
        $this->migrator->add('forms.member_registration_notification_recipient_role_id', null);
    }
};
