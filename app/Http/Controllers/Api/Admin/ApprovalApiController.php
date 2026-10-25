<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\Approvable;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Approvals\ApprovalHistoryRequest;
use App\Models\Traits\HasApprovals;
use App\Support\MorphMap;
use Illuminate\Http\JsonResponse;

class ApprovalApiController extends ApiController
{
    /**
     * Get approval history for a model.
     */
    public function history(ApprovalHistoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $approvable = $this->resolveApprovable($validated['approvable_type'], $validated['approvable_id']);

        if (! $approvable) {
            return $this->jsonNotFound('Model not found');
        }

        // The history carries approver names and their internal notes, so reading it follows
        // the approvable's own view ability rather than being open to any authenticated user.
        $this->authorizeApi('view', $approvable);

        $approvals = $approvable->approvals()
            ->with([
                'user:id,name,profile_photo_path',
                'revertedBy:id,name,profile_photo_path',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->jsonSuccess($approvals);
    }

    /**
     * Resolve the approvable model from its morph alias and ID.
     */
    protected function resolveApprovable(string $type, string $id): ?Approvable
    {
        $modelClass = MorphMap::classFor($type);

        if ($modelClass === null) {
            return null;
        }

        $model = $modelClass::find($id);

        if (! $model) {
            return null;
        }

        $traits = class_uses_recursive($model);

        if (! isset($traits[HasApprovals::class])) {
            return null;
        }

        if (! $model instanceof Approvable) {
            return null;
        }

        return $model;
    }
}
