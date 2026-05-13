<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Str;

class DutiablePolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::DUTIABLE()->label);
    }

    /**
     * Whether the user can manage (edit/delete) this specific Dutiable row.
     *
     * Delegates to DutyPolicy::managePeople, which handles both owning-tenant
     * and cross-tenant assignable-tenant authorization.
     */
    public function manageDutiable(User $user, Dutiable $dutiable): bool
    {
        $duty = $dutiable->duty;

        if (! $duty) {
            return false;
        }

        $targetUser = $dutiable->dutiable_type === User::class
            ? $dutiable->user
            : null;

        return app(DutyPolicy::class)->managePeople($user, $duty, $targetUser);
    }
}
