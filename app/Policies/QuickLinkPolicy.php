<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QuickLinkPolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::QUICK_LINK()->label);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Model $quickLink): bool
    {
        return $this->commonChecker($user, $quickLink, CRUDEnum::READ()->label, $this->pluralModelName, false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Model $quickLink): bool
    {
        return $this->commonChecker($user, $quickLink, CRUDEnum::UPDATE()->label, $this->pluralModelName, false);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Model $quickLink): bool
    {
        return $this->commonChecker($user, $quickLink, CRUDEnum::DELETE()->label, $this->pluralModelName, false);
    }
}
