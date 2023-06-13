<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;

class ReservationPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::RESERVATION()->label);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Reservation $reservation, ModelAuthorizer $authorizer): bool
    {
        $this->authorizer = $authorizer;

        if ($this->commonChecker($user, $reservation, CRUDEnum::READ()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Reservation $reservation, ModelAuthorizer $authorizer): bool
    {
        $this->authorizer = $authorizer;

        if ($this->commonChecker($user, $reservation, CRUDEnum::UPDATE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Reservation $reservation, ModelAuthorizer $authorizer): bool
    {
        $this->authorizer = $authorizer;

        if ($this->commonChecker($user, $reservation, CRUDEnum::DELETE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Reservation $reservation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Reservation $reservation): bool
    {
        return false;
    }
}
