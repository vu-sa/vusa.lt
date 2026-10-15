<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PostCreateRedirect
{
    public function url(User $actor, Model $record, string $destinationRoute, string $destinationAbility, string $indexRoute): string
    {
        if ($actor->can($destinationAbility, $record)) {
            return route($destinationRoute, $record);
        }

        return $actor->can('viewAny', $record::class)
            ? route($indexRoute)
            : route('dashboard');
    }
}
