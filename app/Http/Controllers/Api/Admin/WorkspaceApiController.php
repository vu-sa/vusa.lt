<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Meeting;
use App\Models\Problem;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Support endpoints for workspace management dialogs.
 */
class WorkspaceApiController extends ApiController
{
    /**
     * Search users to invite as members. Gated on member management; existing
     * members are excluded from the results.
     */
    public function memberCandidates(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorizeApi('manageMembers', $workspace);

        $validated = $request->validate([
            'search' => ['required', 'string', 'min:3'],
        ]);

        $users = User::query()
            ->select('id', 'name', 'profile_photo_path')
            ->where('name', 'like', "%{$validated['search']}%")
            ->whereDoesntHave('workspaces', fn ($q) => $q->whereKey($workspace->id))
            ->orderBy('name')
            ->limit(15)
            ->get();

        return $this->jsonSuccess($users);
    }

    /**
     * Search records to link to the workspace. Results are limited to records
     * the requesting user can view, so linking can never expose anything the
     * attacher couldn't already see.
     */
    public function linkCandidates(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorizeApi('update', $workspace);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['meeting', 'problem'])],
            'search' => ['required', 'string', 'min:2'],
        ]);

        $user = $this->requireAuth($request);

        $candidates = match ($validated['type']) {
            'meeting' => $this->meetingCandidates($user, $validated['search']),
            'problem' => $this->problemCandidates($validated['search']),
            default => [],
        };

        return $this->jsonSuccess($candidates);
    }

    /**
     * @return array<int, array{id: string, label: string, meta: string|null}>
     */
    protected function meetingCandidates(User $user, string $search): array
    {
        return Meeting::query()
            ->with('institutions:id,name')
            ->where('title', 'like', "%{$search}%")
            ->latest('start_time')
            ->limit(25)
            ->get()
            ->filter(fn (Meeting $meeting) => $user->can('view', $meeting))
            ->take(10)
            ->map(fn (Meeting $meeting) => [
                'id' => $meeting->id,
                'label' => $meeting->title,
                'meta' => $meeting->institutions->pluck('name')->implode(', '),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: string, label: mixed, meta: string|null}>
     */
    protected function problemCandidates(string $search): array
    {
        return Problem::query()
            ->whereLike('title', "%{$search}%")
            ->latest('occurred_at')
            ->limit(10)
            ->get()
            ->map(fn (Problem $problem) => [
                'id' => $problem->id,
                'label' => $problem->title,
                'meta' => $problem->status,
            ])
            ->values()
            ->all();
    }
}
