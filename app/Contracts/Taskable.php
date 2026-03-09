<?php

namespace App\Contracts;

use App\Tasks\Enums\ActionType;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Contract for models that can have tasks attached to them.
 *
 * Models implementing this contract can be the "taskable" target
 * for Task models via polymorphic relationship.
 */
interface Taskable
{
    /**
     * Get all tasks associated with this model.
     */
    public function tasks(): MorphMany;

    /**
     * Get incomplete tasks of a specific action type.
     */
    public function incompleteTasksOfType(ActionType $actionType): MorphMany;

    /**
     * Get a display name for use in task titles.
     */
    public function getTaskDisplayName(): string;
}
