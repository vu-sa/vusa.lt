<?php

namespace App\Support;

use App\Helpers\AddressivizeHelper;
use Illuminate\Support\Str;

class MailGreeting
{
    public static function forName(?string $name): string
    {
        $firstName = Str::before(trim($name ?? ''), ' ');

        return $firstName === ''
            ? __('mail.greeting_generic')
            : __('mail.greeting', ['name' => AddressivizeHelper::addressivize($firstName)]);
    }
}
