<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;
use App\Enums\Traits\HasCamelCaseLabels;

enum CRUDEnum: string
{
    use HasCamelCaseLabels;
    use HasEnumHelpers;

    case CREATE = 'create';
    case READ = 'read';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case FORCE_DELETE = 'forceDelete';
}
