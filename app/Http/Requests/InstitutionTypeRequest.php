<?php

namespace App\Http\Requests;

use App\Models\InstitutionType;

class InstitutionTypeRequest extends TypeAttributesRequest
{
    protected function typeClass(): string
    {
        return InstitutionType::class;
    }
}
