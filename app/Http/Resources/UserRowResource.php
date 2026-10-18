<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserRowResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->makeVisible(['last_action'])->toArray(),
            'can_update_identity' => (bool) $request->user()?->can('updateIdentity', $this->resource),
        ];
    }
}
