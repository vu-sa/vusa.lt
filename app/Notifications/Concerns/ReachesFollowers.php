<?php

namespace App\Notifications\Concerns;

/**
 * A meeting notice sent to someone only because they follow the institution. It is its own type
 * (FollowedInstitutionActivity), so followers set its channels apart from the overseers' copy.
 */
trait ReachesFollowers
{
    protected bool $viaFollow = false;

    public function viaFollow(): static
    {
        $this->viaFollow = true;

        return $this;
    }
}
