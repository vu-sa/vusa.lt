<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Models\User;
use App\Policies\Traits\HasCommonChecks;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy class for most models in the application.
 * 
 * This class provides common authorization checks for tenant-based models,
 * supporting the hierarchical permission structure of the application.
 */
class ModelPolicy
{
    use HasCommonChecks;

    /**
     * The plural name of the model, used for permission string generation
     */
    protected $pluralModelName;

    /**
     * Constructor injects the ModelAuthorizer service
     */
    public function __construct(public ModelAuthorizer $authorizer) {}

    /**
     * Standard view method that most models will use.
     * Override in child classes when needed.
     *
     * @param User $user The user performing the action
     * @param Model $model The model being accessed
     * @return bool Whether access is permitted
     */
    public function view(User $user, Model $model): bool
    {
        return $this->commonChecker($user, $model, CRUDEnum::READ()->label, $this->pluralModelName);
    }

    /**
     * Standard update method that most models will use.
     * Override in child classes when needed.
     *
     * @param User $user The user performing the action
     * @param Model $model The model being updated
     * @return bool Whether update is permitted
     */
    public function update(User $user, Model $model): bool
    {
        return $this->commonChecker($user, $model, CRUDEnum::UPDATE()->label, $this->pluralModelName);
    }

    /**
     * Standard delete method that most models will use.
     * Override in child classes when needed.
     *
     * @param User $user The user performing the action
     * @param Model $model The model being deleted
     * @return bool Whether deletion is permitted
     */
    public function delete(User $user, Model $model): Response | bool
    {
        return $this->commonChecker($user, $model, CRUDEnum::DELETE()->label, $this->pluralModelName);
    }

    /**
     * Standard forceDelete method for soft-deletable models.
     * This is restricted by default for most models.
     * Override in child classes when needed.
     *
     * @param User $user The user performing the action
     * @param Model $model The model being force-deleted
     * @return bool Whether force deletion is permitted
     */
    public function forceDelete(User $user, Model $model): bool
    {
        return false;
    }
}
