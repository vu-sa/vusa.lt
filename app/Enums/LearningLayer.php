<?php

namespace App\Enums;

enum LearningLayer: string
{
    case Platform = 'platform';
    case Foundation = 'foundation';
    case InstitutionType = 'institution_type';
    case DutyType = 'duty_type';
    case Tenant = 'tenant';

    public function label(): string
    {
        return __("learning.layers.{$this->value}");
    }
}
