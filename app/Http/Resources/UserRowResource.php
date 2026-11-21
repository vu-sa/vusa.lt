<?php

namespace App\Http\Resources;

use App\Enums\LoginRecency;
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
            ...$this->resource->makeHidden([
                'roles',
                'tenants',
                'authored_comments_count',
                'institution_check_ins_count',
                'created_problems_count',
            ])->toArray(),
            'login_recency' => LoginRecency::fromLastAction($this->resource->last_action),
            'can_update_identity' => (bool) $request->user()?->can('updateIdentity', $this->resource),
        ];
    }
}
