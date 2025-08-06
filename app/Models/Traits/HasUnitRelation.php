<?php

namespace App\Models\Traits;

trait HasUnitRelation
{
    /**
     * Returns the method name for the unit relation.
     * The problem is that some models have different method names for getting the unit relation
     * It's better if user is extended from Authenticatable, not from Model, because impersonate package throws errors
     *
     * TODO: Remove this trait - it's non-sensible. The method_exists() check will always be true for known classes,
     * and this dynamic approach makes the code harder to understand and analyze statically.
     */
    public function whichUnitRelation(): string
    {
        // Check for tenant method first
        $class = static::class;
        // @phpstan-ignore-next-line
        if (method_exists($class, 'tenant')) {
            return 'tenant';
        }

        if (method_exists($class, 'tenants')) {
            return 'tenants';
        }

        // Throw exception if no unit relation found.
        throw new \Exception('No unit relation found');
    }
}
