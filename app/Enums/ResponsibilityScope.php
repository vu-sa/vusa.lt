<?php

namespace App\Enums;

use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;

/**
 * What a duty responsibility covers. Backing values are the morph aliases stored in
 * `duty_responsibilities.scope_type`; the most specific scope wins when resolving.
 */
enum ResponsibilityScope: string
{
    case Institution = 'institution';
    case InstitutionType = 'institution_type';
    case Tenant = 'tenant';

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Institution => Institution::class,
            self::InstitutionType => InstitutionType::class,
            self::Tenant => Tenant::class,
        };
    }

    public function labelKey(): string
    {
        return "responsibilities.scopes.{$this->value}";
    }
}
