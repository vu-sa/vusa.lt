<?php

namespace App\Services\ResourceServices;

use App\Models\Institution;
use App\Models\Type;
use Illuminate\Database\Eloquent\Collection;

class InstitutionService
{
    /**
     * Get institutions by type slug
     *
     * @param  string  $typeSlug
     * @return Collection<Institution>
     */
    public function getInstitutionsByTypeSlug($typeSlug): Collection
    {
        $type = Type::where('slug', $typeSlug)->first();

        return $type->institutions;
    }
}
