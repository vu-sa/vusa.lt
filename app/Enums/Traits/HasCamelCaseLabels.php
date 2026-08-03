<?php

namespace App\Enums\Traits;

/**
 * Provides a `label()` instance method that camelCases the enum case's name.
 *
 * e.g. RESERVATION_RESOURCE -> reservationResource.
 */
trait HasCamelCaseLabels
{
    public function label(): string
    {
        $name = strtolower($this->name);

        return preg_replace_callback('/_([a-z])/', fn (array $matches) => strtoupper($matches[1]), $name);
    }
}
