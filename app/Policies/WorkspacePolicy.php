<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

/**
 * Workspaces are user-centric and sit outside the tenant permission matrix:
 * access is granted by explicit membership or by holding an active duty in the
 * attached institution, not by `{resource}.{action}.{scope}` permissions.
 */
class WorkspacePolicy
{
    /**
     * Super admins bypass all checks (mirrors the global permission short-circuit).
     */
    public function before(User $user): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    /**
     * Any authenticated admin user may list their own workspaces; the index
     * query itself is always scoped to accessible workspaces.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->isMember($user) || $workspace->hasInstitutionAccess($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Every collaborator may update: rename the workspace, edit documents,
     * attach/detach links. Realtime document channels gate on this ability.
     */
    public function update(User $user, Workspace $workspace): bool
    {
        return $this->view($user, $workspace);
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $workspace->isAdmin($user);
    }

    /**
     * Invite/remove members, change roles, attach/detach the institution.
     */
    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return $workspace->isAdmin($user);
    }
}
