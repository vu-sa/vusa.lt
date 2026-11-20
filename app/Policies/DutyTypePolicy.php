<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DutyTypePolicy extends ModelPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, 'dutyTypes.read.*');
    }

    public function create(User $user): bool
    {
        return $this->authorizer->allows($user, 'dutyTypes.create.*');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->isSuperAdmin();
    }
}
