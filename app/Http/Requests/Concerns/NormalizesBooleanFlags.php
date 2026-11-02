<?php

namespace App\Http\Requests\Concerns;

/**
 * A query string carries booleans as the strings "true"/"false", which the `boolean` rule
 * rejects outright. Call from prepareForValidation() for flags that can arrive that way.
 */
trait NormalizesBooleanFlags
{
    protected function normalizeBooleanFlags(string ...$keys): void
    {
        foreach ($keys as $key) {
            if ($this->has($key)) {
                $this->merge([$key => $this->boolean($key)]);
            }
        }
    }
}
