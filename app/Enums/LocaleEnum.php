<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;
use App\Enums\Traits\HasCamelCaseLabels;

enum LocaleEnum: string
{
    use HasCamelCaseLabels;
    use HasEnumHelpers;

    case LT = 'lt';
    case EN = 'en';
}
