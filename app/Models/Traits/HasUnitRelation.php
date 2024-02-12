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
        // check for padalinys relation
        if (method_exists($this, 'padalinys')) {
            return 'padalinys';
        }

        // check for padaliniai relation
        if (method_exists($this, 'padaliniai')) {
            return 'padaliniai';
        }

        // Throw exception if no unit relation found.
        throw new \Exception('No unit relation found');
    }
}
