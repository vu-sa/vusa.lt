<?php

namespace App\Policies;

use App\Models\GlossaryTerm;
use App\Models\User;
use App\Services\ModelAuthorizer;

class GlossaryTermPolicy
{
    public function __construct(private ModelAuthorizer $authorizer) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->authorizer->allows($user, 'learningSteps.update.*');
    }

    public function update(User $user, GlossaryTerm $term): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, GlossaryTerm $term): bool
    {
        return $this->create($user);
    }
}
