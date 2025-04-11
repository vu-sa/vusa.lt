<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChangelogItemPolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);

        $this->pluralModelName = Str::plural(ModelEnum::CHANGELOG_ITEM()->label);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Model $changelogItem): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Model $changelogItem): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Model $changelogItem): bool
    {
        return true;
    }
}
