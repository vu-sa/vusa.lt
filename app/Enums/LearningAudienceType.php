<?php

namespace App\Enums;

enum LearningAudienceType: string
{
    case Everyone = 'everyone';
    case DutyType = 'duty_type';
    case InstitutionType = 'institution_type';
    case Responsibility = 'responsibility';
    case Duty = 'duty';
    case Institution = 'institution';
    case Purpose = 'purpose';

    public function label(): string
    {
        return __("learning.audience_types.{$this->value}");
    }
}
