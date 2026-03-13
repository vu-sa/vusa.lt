<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Enums\PermissionScopeEnum;
use App\Models\PlanningProcess;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Policy for PlanningProcess model authorization.
 */
class PlanningProcessPolicy extends ModelPolicy
{
    /**
     * Initialize policy with model name.
     */
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::PLANNING_PROCESS()->label);
    }

    /**
     * All authenticated users in the admin area can view planning processes.
     */
    public function view(User $user, Model $planningProcess): bool
    {
        /** @var PlanningProcess $planningProcess */
        if ($planningProcess->moderator_user_id === $user->id) {
            return true;
        }

        return $this->commonChecker($user, $planningProcess, CRUDEnum::READ()->label, $this->pluralModelName, false);
    }

    /**
     * Override update to check moderator access and locking logic.
     */
    public function update(User $user, Model $planningProcess): bool
    {
        /** @var PlanningProcess $planningProcess */

        // Locked processes require global (all-scope) update permission
        if ($planningProcess->isLocked()) {
            return $this->authorizer->forUser($user)->check($this->pluralModelName.'.'.CRUDEnum::UPDATE()->label.'.'.PermissionScopeEnum::ALL()->label);
        }

        // Moderator can edit their assigned process
        if ($planningProcess->moderator_user_id === $user->id) {
            return true;
        }

        return $this->commonChecker($user, $planningProcess, CRUDEnum::UPDATE()->label, $this->pluralModelName, false);
    }

    /**
     * Determine if the user can approve goals/documents (coordinator only, not the assigned moderator).
     */
    public function approve(User $user, Model $planningProcess): bool
    {
        /** @var PlanningProcess $planningProcess */

        // Moderators cannot approve their own planning process
        if ($planningProcess->moderator_user_id === $user->id) {
            return false;
        }

        return $this->commonChecker($user, $planningProcess, CRUDEnum::UPDATE()->label, $this->pluralModelName, false);
    }

    /**
     * Override delete method.
     */
    public function delete(User $user, Model $planningProcess): bool
    {
        return $this->commonChecker($user, $planningProcess, CRUDEnum::DELETE()->label, $this->pluralModelName, false);
    }

    /**
     * Override restore method.
     */
    public function restore(User $user, Model $planningProcess): bool
    {
        return $this->commonChecker($user, $planningProcess, CRUDEnum::DELETE()->label, $this->pluralModelName, false);
    }
}
