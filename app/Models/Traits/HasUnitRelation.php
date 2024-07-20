<?php

namespace App\Models\Traits;

trait HasUnitRelation
{
    /**
     * Returns the method name for the unit relation.
     * The problem is that some models have different method names for getting the unit relation
     * It's better if user is extended from Authenticatable, not from Model, because impersonate package throws errors
     */
    public function whichUnitRelation(): string
    {
        if (method_exists($this, 'tenant')) {
            return 'tenant';
        }

        if (method_exists($this, 'tenants')) {
            return 'tenants';
        }

        // Throw exception if no unit relation found.
        throw new \Exception('No unit relation found');
    }
}
