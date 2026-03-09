<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Approvable;
use App\Enums\ApprovalDecision;
use App\Enums\ModelEnum;
use App\Http\Controllers\AdminController;
use App\Models\Approval;
use App\Models\Traits\HasApprovals;
use App\Services\ApprovalService;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Enum\Laravel\Rules\EnumRule;

class ApprovalController extends AdminController
{
    public function __construct(
        public Authorizer $authorizer,
        protected ApprovalService $approvalService
    ) {}

    /**
     * Store a new approval decision.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'approvable_type' => ['required', new EnumRule(ModelEnum::class)],
            'approvable_id' => 'required|string',
            'decision' => 'required|string|in:approved,rejected,cancelled',
            'notes' => 'nullable|string|max:1000',
            'step' => 'nullable|integer|min:1',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $approvable = $this->resolveApprovable($validated['approvable_type'], $validated['approvable_id']);

        if (! $approvable) {
            return back()->with('error', __('Modelis nerastas.'));
        }

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
    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'approvable_type' => ['required', new EnumRule(ModelEnum::class)],
            'approvable_ids' => 'required|array|min:1',
            'approvable_ids.*' => 'required|string',
            'decision' => 'required|string|in:approved,rejected,cancelled',
            'notes' => 'nullable|string|max:1000',
            'step' => 'nullable|integer|min:1',
        ]);

        $decision = ApprovalDecision::from($validated['decision']);
        $user = auth()->user();
        $notes = $validated['notes'] ?? null;
        $step = $validated['step'] ?? null;

        $approvables = collect($validated['approvable_ids'])
            ->map(fn ($id) => $this->resolveApprovable($validated['approvable_type'], $id))
            ->filter();

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
     * Resolve the approvable model from type and ID.
     */
    protected function resolveApprovable(string $type, string $id)
    {
        $formatted = Str::ucfirst(Str::camel($type));

        if ($formatted === 'ReservationResource') {
            $modelClass = 'App\\Models\\Pivots\\ReservationResource';
        } else {
            $modelClass = 'App\\Models\\'.$formatted;
        }

        if (! class_exists($modelClass)) {
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
    public function history(Request $request)
    {
        $validated = $request->validate([
            'approvable_type' => ['required', new EnumRule(ModelEnum::class)],
            'approvable_id' => 'required|string',
        ]);

        $approvable = $this->resolveApprovable($validated['approvable_type'], $validated['approvable_id']);

        if (! $approvable) {
            return response()->json(['error' => 'Model not found'], 404);
        }

        $approvals = $approvable->approvals()
            ->with('user:id,name,profile_photo_path')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['approvals' => $approvals]);
    }
}
