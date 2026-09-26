<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Institution;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InstitutionPolicy extends ModelPolicy
{
    /**
     * These models belong to a single tenant through a `tenant` relation.
     */
    #[\Override]
    protected bool $hasManyTenants = false;

    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::INSTITUTION->label());
    }

    /**
     * Every admin may open the collection: active institutions are public, and the scoped search
     * key adds the user's padalinys and own/related institutions when their permissions allow.
     */
    #[\Override]
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * The read-only record: an active institution is public (vusa.lt contacts), so anyone may
     * read its overview, members and public meetings; the rest stays behind `view()`.
     *
     * @param  Institution  $institution
     */
    public function viewSummary(User $user, Model $institution): bool
    {
        return (bool) $institution->is_active || $this->view($user, $institution);
    }

    /**
     * Following means hearing about the institution's meetings, so a reader of the public face
     * may follow only where those meetings are public (followers get only what they may read).
     *
     * @param  Institution  $institution
     */
    public function follow(User $user, Model $institution): bool
    {
        return ((bool) $institution->is_active && $institution->has_public_meetings) || $this->view($user, $institution);
    }

    /**
     * Determine whether the user can view the model.
     */
    #[\Override]
    public function view(User $user, Model $institution): bool
    {
        return $this->commonChecker($user, $institution, CRUDEnum::READ->label(), $this->pluralModelName, false);
    }

    /**
     * Determine whether the user can update the model.
     */
    #[\Override]
    public function update(User $user, Model $institution): bool
    {
        // For institutions, "own" scope only applies to read operations
        // Since institutions.update.own permission doesn't exist, commonChecker will only
        // check padalinys and * scopes automatically
        return $this->commonChecker($user, $institution, CRUDEnum::UPDATE->label(), $this->pluralModelName, false);
    }

    /**
     * Determine whether the user can delete the model.
     */
    #[\Override]
    public function delete(User $user, Model $institution): bool
    {
        // For institutions, "own" scope only applies to read operations
        // Since institutions.delete.own permission doesn't exist, commonChecker will only
        // check padalinys and * scopes automatically
        return $this->commonChecker($user, $institution, CRUDEnum::DELETE->label(), $this->pluralModelName, false);
    }
}
