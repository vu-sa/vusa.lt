<?php

namespace App\Actions;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Support\Collection;

class SerializeResourceManagers
{
    /** @return Collection<int, array{id: string, name: string, email: ?string, phone: ?string, facebook_url: ?string, profile_photo_path: ?string}> */
    public static function execute(Resource $resource): Collection
    {
        return $resource->managers()->map(fn (User $user): array => [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'facebook_url' => $user->facebook_url,
            'profile_photo_path' => $user->profile_photo_path,
        ])->values();
    }
}
