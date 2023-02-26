<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\News;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;

class NewsPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::NEWS()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, News $news, ModelAuthorizer $modelAuthorizer)
    {
        $this->authorizer = $modelAuthorizer;

        if ($this->commonChecker($user, $news, CRUDEnum::READ()->label, $this->pluralModelName, false)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, News $news, ModelAuthorizer $modelAuthorizer)
    {
        $this->authorizer = $modelAuthorizer;

        if ($this->commonChecker($user, $news, CRUDEnum::UPDATE()->label, $this->pluralModelName, false)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, News $news, ModelAuthorizer $modelAuthorizer)
    {
        $this->authorizer = $modelAuthorizer;

        if ($this->commonChecker($user, $news, CRUDEnum::DELETE()->label, $this->pluralModelName, false)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, News $news)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, News $news)
    {
        //
    }
}
