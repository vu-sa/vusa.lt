<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class FormSettings extends Settings
{
    public ?string $member_registration_form_id;

    public ?string $member_registration_notification_recipient_role_id;

    public static function group(): string
    {
        return 'forms';
    }
}
