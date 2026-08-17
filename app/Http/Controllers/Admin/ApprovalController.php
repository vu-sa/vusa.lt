<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Approvable;
use App\Enums\ApprovalDecision;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Requests\Approvals\ApprovalHistoryRequest;
use App\Http\Requests\Approvals\BulkStoreApprovalRequest;
use App\Http\Requests\Approvals\ResolveApprovalsRequest;
use App\Http\Requests\Approvals\StoreApprovalRequest;
use App\Models\Approval;
use App\Models\Traits\HasApprovals;
use App\Services\ApprovalService;
use App\Support\MorphMap;

class ApprovalController extends AdminController
{
    use ApiResponses;

    public function __construct(protected ApprovalService $approvalService) {}

    /**
     * Store a new approval decision.
     */
    public function store(StoreApprovalRequest $request)
    {
        $validated = $request->validated();

        $approvable = $this->resolveApprovable($validated['approvable_type'], $validated['approvable_id']);

        if (! $approvable) {
            return back()->with('error', __('Modelis nerastas.'));
        }

        // ApprovalService::approve() has the final say via canBeApprovedBy(), but that is a
        // per-step approver check, not an access check — a coarse `view` gate here keeps the
        // endpoint from being a probe for records the user cannot see at all.
        $this->handleAuthorization('view', $approvable);

        $decision = ApprovalDecision::from($validated['decision']);
        $user = auth()->user();

        // Handle partial quantity approval for reservation resources
        $approvedQuantity = $validated['quantity'] ?? null;
        if ($approvedQuantity !== null && $decision === ApprovalDecision::Approved) {
            if (method_exists($approvable, 'updateApprovedQuantity')) {
                $approvable->updateApprovedQuantity($approvedQuantity);
            }
        }

        try {
            $this->approvalService->approve(
                $approvable,
                $user,
                $decision,
                $validated['notes'] ?? null,
                $validated['step'] ?? null
            );

            $message = match ($decision) {
                ApprovalDecision::Approved => __('Patvirtinta sėkmingai.'),
                ApprovalDecision::Rejected => __('Atmesta sėkmingai.'),
                ApprovalDecision::Cancelled => __('Atšaukta sėkmingai.'),
            };

            return back()->with('success', $message);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk approve multiple items.
     */
    public function bulkStore(BulkStoreApprovalRequest $request)
    {
        $validated = $request->validated();

        $decision = ApprovalDecision::from($validated['decision']);
        $user = auth()->user();
        $notes = $validated['notes'] ?? null;
        $step = $validated['step'] ?? null;

        $approvables = collect($validated['approvable_ids'])
            ->map(fn ($id) => $this->resolveApprovable($validated['approvable_type'], $id))
            ->filter()
            // Same coarse gate as store(); bulkApprove() still applies canBeApprovedBy() per item.
            ->each(fn ($approvable) => $this->handleAuthorization('view', $approvable));

        $result = $this->approvalService->bulkApprove($approvables, $user, $decision, $notes, $step);
        $approvals = $result['approvals'];
        $errors = $result['errors'];

        $count = $approvals->count();

        // If no approvals succeeded and there are errors, show the first error
        if ($count === 0 && ! empty($errors)) {
            return back()->with('error', $errors[0]);
        }

        $message = match ($decision) {
            ApprovalDecision::Approved => __(':count elementų patvirtinta.', ['count' => $count]),
            ApprovalDecision::Rejected => __(':count elementų atmesta.', ['count' => $count]),
            ApprovalDecision::Cancelled => __(':count elementų atšaukta.', ['count' => $count]),
        };

        // If some succeeded but some failed, add warning about skipped items
        if (! empty($errors)) {
            $skipped = count($errors);
            $message .= ' '.__(':skipped praleišta dėl klaidų.', ['skipped' => $skipped]);
        }

        return back()->with('success', $message);
    }

    /**
     * Fully resolve items — advance them straight to their final approved state.
     *
     * Housekeeping for stale requests: closing out something nobody ever collected should not mean
     * clicking approve, hand over and mark returned in sequence. Each intermediate transition is
     * still recorded, so the history shows what happened.
     */
    public function resolve(ResolveApprovalsRequest $request)
    {
        $validated = $request->validated();

        $user = auth()->user();
        $notes = $validated['notes'] ?? null;

        $resolved = 0;
        $errors = [];

        foreach ($validated['approvable_ids'] as $id) {
            $approvable = $this->resolveApprovable($validated['approvable_type'], $id);

            if (! $approvable) {
                continue;
            }

            $this->handleAuthorization('view', $approvable);

            try {
                if ($this->approvalService->fastForward($approvable, $user, $notes)->isNotEmpty()) {
                    $resolved++;
                }
            } catch (\InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($resolved === 0) {
            return back()->with('error', $errors[0] ?? __('Nepavyko užbaigti nė vieno elemento.'));
        }

        $message = __(':count elementų užbaigta.', ['count' => $resolved]);

        if (! empty($errors)) {
            $message .= ' '.__(':skipped praleista dėl klaidų.', ['skipped' => count($errors)]);
        }

        return back()->with('success', $message);
    }

    /**
     * Resolve the approvable model from its morph alias and ID.
     *
     * The alias comes straight from the morph map, so pivots resolve like any other model —
     * this used to rebuild a class name out of the string and needed a hardcoded exception
     * for ReservationResource, which does not live under App\Models.
     */
    protected function resolveApprovable(string $type, string $id)
    {
        $modelClass = MorphMap::classFor($type);

        if ($modelClass === null) {
            return null;
        }

        $model = $modelClass::find($id);

        // Verify model implements required interfaces
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

    /**
     * Get approval history for a model.
     */
    public function history(ApprovalHistoryRequest $request)
    {
        $validated = $request->validated();

        $approvable = $this->resolveApprovable($validated['approvable_type'], $validated['approvable_id']);

        if (! $approvable) {
            return $this->jsonNotFound('Model not found');
        }

        // The history carries approver names and their internal notes, so reading it follows
        // the approvable's own view ability rather than being open to any authenticated user.
        $this->handleAuthorization('view', $approvable);

        $approvals = $approvable->approvals()
            ->with('user:id,name,profile_photo_path')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->jsonSuccess($approvals);
    }
}
