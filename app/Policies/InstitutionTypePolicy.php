<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InstitutionTypePolicy extends ModelPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, 'institutionTypes.read.*');
    }

    public function create(User $user): bool
    {
        return $this->authorizer->allows($user, 'institutionTypes.create.*');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->isSuperAdmin();
    }
}
