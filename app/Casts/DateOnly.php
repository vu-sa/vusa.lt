<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A calendar day: serialised as `Y-m-d`, because the plain `date` cast sends Vilnius midnight as
 * the previous day in UTC. Input with an offset is read in Vilnius time, so `…T22:00:00Z` stays the next day.
 *
 * @implements CastsAttributes<Carbon, mixed>
 */
class DateOnly implements CastsAttributes, SerializesCastableAttributes
{
    /** Like the built-in `date` cast: mutating the returned Carbon must not change the model. */
    public bool $withoutObjectCaching = true;

    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        return $value === null ? null : Carbon::parse($value)->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->setTimezone(config('app.timezone'))->toDateString();
    }

    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        // toArray() hands this the value after serializeDate(), i.e. as an ISO string in UTC.
        return $value === null ? null : Carbon::parse($value)->setTimezone(config('app.timezone'))->toDateString();
    }
}
