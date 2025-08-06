<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Task;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TaskPolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::TASK()->label);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  Task  $task
     */
    public function update(User $user, Model $task): bool
    {
        if ($task->users->contains($user)) {
            return true;
        }

        return $this->commonChecker($user, $task, CRUDEnum::UPDATE()->label);
    }
}
