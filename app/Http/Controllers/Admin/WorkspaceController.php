<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorkspaceMemberRole;
use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceLink;
use App\Support\WorkspaceLinkables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkspaceController extends AdminController
{
    /**
     * Display the user's accessible workspaces.
     */
    public function index(Request $request)
    {
        $this->handleAuthorization('viewAny', Workspace::class);

        $user = $request->user();

        $workspaces = $this->accessibleWorkspacesQuery($user)
            ->with(['institution:id,name', 'creator:id,name', 'members:id,name,profile_photo_path'])
            ->withCount('documents')
            ->latest('updated_at')
            ->get();

        return $this->inertiaResponse('Admin/Workspaces/IndexWorkspace', [
            'workspaces' => $workspaces,
            'userInstitutions' => $this->userInstitutionOptions($user),
        ]);
    }

    /**
     * Store a newly created workspace and seed the creator as its admin.
     */
    public function store(StoreWorkspaceRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $this->assertInstitutionAttachable($user, $validated['institution_id'] ?? null);

        $workspace = Workspace::create([
            ...$validated,
            'created_by' => $user->id,
        ]);

        $workspace->members()->attach($user->id, ['role' => WorkspaceMemberRole::Admin->value]);

        return $this->redirectWithSuccess(
            'workspaces.show',
            trans_choice('messages.created', 0, ['model' => trans_choice('entities.workspace.model', 1)]),
            $workspace
        );
    }

    /**
     * Display the specified workspace.
     */
    public function show(Request $request, Workspace $workspace)
    {
        $this->handleAuthorization('view', $workspace);

        $user = $request->user();

        $workspace->load([
            'institution:id,name',
            'creator:id,name',
            'members:id,name,profile_photo_path',
            'documents' => fn ($query) => $query
                ->select('id', 'workspace_id', 'title', 'updated_by', 'updated_at')
                ->with('editor:id,name')
                ->latest('updated_at'),
            'links.linkable',
            'links.addedBy:id,name',
        ]);

        return $this->inertiaResponse('Admin/Workspaces/ShowWorkspace', [
            'workspace' => [
                ...$workspace->toArray(),
                'links' => $workspace->links->map(fn (WorkspaceLink $link) => [
                    'id' => $link->id,
                    'type' => WorkspaceLinkables::aliasFor($link->linkable),
                    'linkable' => $link->linkable,
                    'added_by' => $link->addedBy?->only(['id', 'name']),
                    'created_at' => $link->created_at,
                ]),
            ],
            'userInstitutions' => $this->userInstitutionOptions($user),
            'canManageMembers' => $user->can('manageMembers', $workspace),
            'canDelete' => $user->can('delete', $workspace),
        ]);
    }

    /**
     * Update the workspace name/description (or institution, admins only —
     * enforced in the form request).
     */
    public function update(UpdateWorkspaceRequest $request, Workspace $workspace)
    {
        $validated = $request->validated();

        if (array_key_exists('institution_id', $validated)) {
            $this->assertInstitutionAttachable($request->user(), $validated['institution_id']);
        }

        $workspace->update($validated);

        return $this->redirectBackWithSuccess(trans_choice('messages.updated', 0, ['model' => trans_choice('entities.workspace.model', 1)]));
    }

    /**
     * Soft-delete the workspace.
     */
    public function destroy(Workspace $workspace)
    {
        $this->handleAuthorization('delete', $workspace);

        $workspace->delete();

        return $this->redirectToIndexWithInfo('workspaces', trans_choice('messages.deleted', 0, ['model' => trans_choice('entities.workspace.model', 1)]));
    }

    /**
     * Invite a user as a workspace member.
     */
    public function addMember(Request $request, Workspace $workspace)
    {
        $this->handleAuthorization('manageMembers', $workspace);

        $validated = $request->validate([
            'user_id' => ['required', 'string', 'exists:users,id'],
            'role' => ['required', Rule::enum(WorkspaceMemberRole::class)],
        ]);

        $workspace->members()->syncWithoutDetaching([
            $validated['user_id'] => ['role' => $validated['role']],
        ]);

        return $this->redirectBackWithSuccess(__('workspaces.member_added'));
    }

    /**
     * Change a member's role.
     */
    public function updateMember(Request $request, Workspace $workspace, User $user)
    {
        $this->handleAuthorization('manageMembers', $workspace);

        $validated = $request->validate([
            'role' => ['required', Rule::enum(WorkspaceMemberRole::class)],
        ]);

        if ($validated['role'] !== WorkspaceMemberRole::Admin->value && $this->isLastAdmin($workspace, $user)) {
            return $this->redirectBackWithError(__('workspaces.last_admin'));
        }

        $workspace->members()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        return $this->redirectBackWithSuccess(__('workspaces.member_updated'));
    }

    /**
     * Remove a member from the workspace.
     */
    public function removeMember(Request $request, Workspace $workspace, User $user)
    {
        $this->handleAuthorization('manageMembers', $workspace);

        if ($this->isLastAdmin($workspace, $user)) {
            return $this->redirectBackWithError(__('workspaces.last_admin'));
        }

        $workspace->members()->detach($user->id);

        return $this->redirectBackWithInfo(__('workspaces.member_removed'));
    }

    /**
     * Link a record (meeting, agenda item, problem, institution) to the workspace.
     */
    public function attachLink(Request $request, Workspace $workspace)
    {
        $this->handleAuthorization('update', $workspace);

        $validated = $request->validate([
            'linkable_type' => ['required', Rule::in(array_keys(WorkspaceLinkables::TYPES))],
            'linkable_id' => ['required', 'string'],
        ]);

        $linkable = WorkspaceLinkables::resolve($validated['linkable_type'], $validated['linkable_id']);

        if ($linkable === null) {
            return $this->redirectBackWithError(__('workspaces.link_not_found'));
        }

        // Linking exposes the record to every workspace collaborator, so the
        // person attaching it must be able to view it themselves.
        $this->handleAuthorization('view', $linkable);

        $workspace->links()->firstOrCreate([
            'linkable_type' => $linkable::class,
            'linkable_id' => $linkable->getKey(),
        ], [
            'added_by' => $request->user()->id,
        ]);

        return $this->redirectBackWithSuccess(__('workspaces.link_added'));
    }

    /**
     * Remove a linked record from the workspace.
     */
    public function detachLink(Workspace $workspace, WorkspaceLink $link)
    {
        $this->handleAuthorization('update', $workspace);

        abort_if($link->workspace_id !== $workspace->id, 403);

        $link->delete();

        return $this->redirectBackWithInfo(__('workspaces.link_removed'));
    }

    /**
     * Workspaces the user may access: explicit membership or an active duty in
     * the attached institution.
     *
     * @return Builder<Workspace>
     */
    protected function accessibleWorkspacesQuery(User $user)
    {
        if ($user->isSuperAdmin()) {
            return Workspace::query();
        }

        $institutionIds = $user->current_duties()->pluck('duties.institution_id')->filter()->unique();

        return Workspace::query()->where(
            fn ($query) => $query
                ->whereHas('members', fn ($q) => $q->whereKey($user->id))
                ->orWhereIn('institution_id', $institutionIds)
        );
    }

    /**
     * Institutions offered when attaching a workspace to an institution — the
     * ones where the user currently holds a duty.
     *
     * @return array<int, array{id: string, name: mixed}>
     */
    protected function userInstitutionOptions(User $user): array
    {
        return $user->current_duties()
            ->with('institution:id,name')
            ->get()
            ->pluck('institution')
            ->filter()
            ->unique('id')
            ->map(fn ($institution) => ['id' => $institution->id, 'name' => $institution->name])
            ->values()
            ->all();
    }

    /**
     * Attaching an institution grants its representatives access, so non-super
     * admins may only attach institutions they currently hold a duty in.
     */
    protected function assertInstitutionAttachable(User $user, ?string $institutionId): void
    {
        if ($institutionId === null || $user->isSuperAdmin()) {
            return;
        }

        abort_unless(
            $user->current_duties()->where('duties.institution_id', $institutionId)->exists(),
            403,
            __('workspaces.institution_not_allowed')
        );
    }

    /**
     * Whether removing/demoting this member would leave the workspace without
     * any admin.
     */
    protected function isLastAdmin(Workspace $workspace, User $user): bool
    {
        $adminIds = $workspace->members()
            ->wherePivot('role', WorkspaceMemberRole::Admin->value)
            ->pluck('users.id');

        return $adminIds->count() === 1 && (string) $adminIds->first() === (string) $user->id;
    }
}
