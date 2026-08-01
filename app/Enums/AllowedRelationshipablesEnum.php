<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;
use App\Enums\Traits\HasCamelCaseLabels;

enum AllowedRelationshipablesEnum: string
{
    use HasCamelCaseLabels;
    use HasEnumHelpers;

    case INSTITUTION = 'INSTITUTION';
    case TYPE = 'TYPE';
}
