<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Document;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Policy for Document model authorization
 */
class DocumentPolicy extends ModelPolicy
{
    /**
     * Initialize policy with model name
     */
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::DOCUMENT()->label);
    }

    /**
     * Standard view method that most models will use.
     * Override in child classes when needed.
     *
     * @param  User  $user  The user performing the action
     * @param  Model  $model  The model being accessed
     * @return bool Whether access is permitted
     */
    public function view(User $user, Model $model): bool
    {
        return $this->commonChecker($user, $model, CRUDEnum::READ()->label, $this->pluralModelName, false);
    }

    /**
     * Standard update method that most models will use.
     * Override in child classes when needed.
     *
     * @param  User  $user  The user performing the action
     * @param  Model  $model  The model being updated
     * @return bool Whether update is permitted
     */
    public function update(User $user, Model $model): bool
    {
        return $this->commonChecker($user, $model, CRUDEnum::UPDATE()->label, $this->pluralModelName, false);
    }

    /**
     * Standard delete method that most models will use.
     * Override in child classes when needed.
     *
     * @param  User  $user  The user performing the action
     * @param  Model  $model  The model being deleted
     * @return bool Whether deletion is permitted
     */
    public function delete(User $user, Model $model): bool
    {
        return $this->commonChecker($user, $model, CRUDEnum::DELETE()->label, $this->pluralModelName, false);
    }
}
