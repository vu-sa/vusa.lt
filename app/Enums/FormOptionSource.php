<?php

namespace App\Enums;

enum FormOptionSource: string
{
    case Tenant = 'tenant';
    case Institution = 'institution';

    public function labelKey(): string
    {
        return match ($this) {
            self::Tenant => 'forms.field_models.tenant',
            self::Institution => 'forms.field_models.institution',
        };
    }
}
