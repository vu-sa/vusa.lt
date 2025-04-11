<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Policy for News model authorization
 */
class NewsPolicy extends ModelPolicy
{
    /**
     * Initialize policy with model name
     */
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::NEWS()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * Override with specific parameter - setting hasManyTenants to false
     */
    public function view(User $user, Model $news): bool
    {
        // News belongs to a single tenant, so we use hasManyTenants=false
        return $this->commonChecker($user, $news, CRUDEnum::READ()->label, $this->pluralModelName, false);
    }

    /**
     * Override update method to ensure proper type hinting
     */
    public function update(User $user, Model $news): bool
    {
        return $this->commonChecker($user, $news, CRUDEnum::UPDATE()->label, $this->pluralModelName, false);
    }

    /**
     * Override delete method to ensure proper type hinting
     */
    public function delete(User $user, Model $news): bool
    {
        return $this->commonChecker($user, $news, CRUDEnum::DELETE()->label, $this->pluralModelName, false);
    }
}
