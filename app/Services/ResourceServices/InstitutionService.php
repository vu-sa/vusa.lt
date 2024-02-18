<?php

namespace App\Services\ResourceServices;

use App\Models\Type;
use App\Models\Institution;

class InstitutionService
{
    public function getInstitutionsByTypeSlug($typeSlug)
    {
        $type = Type::where('slug', $typeSlug)->first();
        return $type->institutions;
    }
}
