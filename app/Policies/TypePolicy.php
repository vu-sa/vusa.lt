<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TypePolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::TYPE()->label);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Model $type): bool
    {
        return $user->isSuperAdmin();
    }
}
