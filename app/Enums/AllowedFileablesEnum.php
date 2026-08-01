<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;
use App\Enums\Traits\HasCamelCaseLabels;

enum AllowedFileablesEnum: string
{
    use HasCamelCaseLabels;
    use HasEnumHelpers;

    case DUTY = 'DUTY';
    case INSTITUTION = 'INSTITUTION';
    case MEETING = 'MEETING';
    case TYPE = 'TYPE';
    case USER = 'USER';
}
