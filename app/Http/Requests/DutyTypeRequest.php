<?php

namespace App\Http\Requests;

use App\Models\DutyType;

class DutyTypeRequest extends TypeAttributesRequest
{
    protected function typeClass(): string
    {
        return DutyType::class;
    }
}
