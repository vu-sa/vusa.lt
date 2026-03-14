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
     * Allow viewing if user is moderator, editor, same-tenant member, or has read permission.
     */
    public function view(User $user, Model $planningProcess): bool
    {
        /** @var PlanningProcess $planningProcess */
        if ($planningProcess->moderator_user_id === $user->id) {
            return true;
        }

        if ($planningProcess->isEditor($user)) {
            return true;
        }

        // Any user in the same tenant can view (read-only)
        if ($user->tenants()->where('tenants.id', $planningProcess->tenant_id)->exists()) {
            return true;
        }

        return $this->commonChecker($user, $planningProcess, CRUDEnum::READ()->label, $this->pluralModelName, false);
    }

    /**
     * Allow updating if user is moderator, editor, or has update permission.
     * Locked processes require global (all-scope) update permission.
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

        // Editors can edit the process
        if ($planningProcess->isEditor($user)) {
            return true;
        }

        return $this->commonChecker($user, $planningProcess, CRUDEnum::UPDATE()->label, $this->pluralModelName, false);
    }

    /**
     * Only coordinators can approve goals/documents — moderators and editors cannot.
     */
    public function approve(User $user, Model $planningProcess): bool
    {
        /** @var PlanningProcess $planningProcess */

        // Moderators cannot approve their own planning process
        if ($planningProcess->moderator_user_id === $user->id) {
            return false;
        }

        // Editors cannot approve
        if ($planningProcess->isEditor($user)) {
            return false;
        }

        return $this->commonChecker($user, $planningProcess, CRUDEnum::UPDATE()->label, $this->pluralModelName, false);
    }

    /**
     * Only coordinators can assign moderators — moderators and editors cannot.
     */
    public function assignModerator(User $user, Model $planningProcess): bool
    {
        /** @var PlanningProcess $planningProcess */

        // Moderators cannot assign themselves
        if ($planningProcess->moderator_user_id === $user->id) {
            return false;
        }

        // Editors cannot assign moderators
        if ($planningProcess->isEditor($user)) {
            return false;
        }

        return $this->commonChecker($user, $planningProcess, CRUDEnum::UPDATE()->label, $this->pluralModelName, false);
    }

    /**
     * Moderators and coordinators can manage editors. Not allowed on locked processes.
     */
    public function manageEditors(User $user, Model $planningProcess): bool
    {
        /** @var PlanningProcess $planningProcess */
        if ($planningProcess->isLocked()) {
            return false;
        }

        // Moderator can manage editors for their process
        if ($planningProcess->moderator_user_id === $user->id) {
            return true;
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
