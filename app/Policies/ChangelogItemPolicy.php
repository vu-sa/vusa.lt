<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\ChangelogItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;
use App\Services\ModelAuthorizer as Authorizer;

class ChangelogItemPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct(public Authorizer $authorizer)
    {
        parent::__construct($authorizer);

        $this->pluralModelName = Str::plural(ModelEnum::ROLE()->label);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ChangelogItem $changelogItem): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ChangelogItem $changelogItem): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ChangelogItem $changelogItem): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ChangelogItem $changelogItem): bool
    {
        return false;
    }
}
