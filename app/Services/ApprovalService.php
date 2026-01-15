<?php

namespace App\Services;

use App\Contracts\Approvable;
use App\Enums\ApprovalDecision;
use App\Events\ApprovalDecisionMade;
use App\Events\ApprovalFlowCompleted;
use App\Events\ApprovalRequested;
use App\Models\Approval;
use App\Models\Traits\HasApprovals;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    /**
     * Request approval for a model.
     * Dispatches ApprovalRequested event which triggers task creation and notifications.
     *
     * @param  Model&HasApprovals&Approvable  $approvable
     */
    public function requestApproval(Model $approvable): void
    {
        event(new ApprovalRequested($approvable));
    }

    /**
     * Create an approval decision for a model.
     *
     * @param  Model&HasApprovals&Approvable  $approvable
     *
     * @throws \InvalidArgumentException If user cannot approve at the given step
     */
    public function approve(
        Model $approvable,
        User $user,
        ApprovalDecision $decision,
        ?string $notes = null,
        ?int $step = null
    ): Approval {
        $step = $step ?? $approvable->currentApprovalStep();

        // Validate user can approve at this step with this decision
        if (! $approvable->canBeApprovedBy($user, $step, $decision)) {
            throw new \InvalidArgumentException('User cannot approve this item at the given step.');
        }

        // Validate that this decision is allowed for the current state
        if (method_exists($approvable, 'isDecisionAllowed') && ! $approvable->isDecisionAllowed($decision)) {
            throw new \InvalidArgumentException(__('Šis veiksmas negalimas dabartinėje būsenoje.'));
        }

        $approval = DB::transaction(function () use ($approvable, $user, $decision, $notes, $step) {
            $approval = Approval::create([
                'approvable_type' => get_class($approvable),
                'approvable_id' => $approvable->id,
                'user_id' => $user->id,
                'decision' => $decision,
                'step' => $step,
                'notes' => $notes,
            ]);

            return $approval;
        });

        // Dispatch decision event
        event(new ApprovalDecisionMade($approval, $approvable));

        // Check if this decision completes the step or terminates the flow
        $this->handlePostDecision($approvable, $decision, $step);

        return $approval;
    }

    /**
     * Bulk approve multiple models.
     *
     * @param  Collection<int, Model&HasApprovals&Approvable>  $approvables
     * @return array{approvals: Collection<int, Approval>, errors: array<string>}
     */
    public function bulkApprove(
        Collection $approvables,
        User $user,
        ApprovalDecision $decision,
        ?string $notes = null,
        ?int $step = null
    ): array {
        $approvals = collect();
        $errors = [];

        foreach ($approvables as $approvable) {
            try {
                $approval = $this->approve($approvable, $user, $decision, $notes, $step);
                $approvals->push($approval);
            } catch (\InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }

        return ['approvals' => $approvals, 'errors' => $errors];
    }

    /**
     * Handle logic after a decision is made.
     *
     * @param  Model&HasApprovals&Approvable  $approvable
     */
    protected function handlePostDecision(Model $approvable, ApprovalDecision $decision, int $step): void
    {
        // Refresh to get updated approval counts
        $approvable->refresh();

        // Terminating decisions (reject/cancel) immediately complete the flow
        if ($decision->isTerminating()) {
            $this->completeFlow($approvable, $decision, $step);

            return;
        }

        // Check if this step is now complete
        if ($approvable->isStepComplete($step)) {
            $flow = $approvable->getApprovalFlow();

            // If no flow or single step, complete the flow
            if (! $flow || $flow->isSingleStep()) {
                $this->completeFlow($approvable, ApprovalDecision::Approved, $step);

                return;
            }

            // Multi-step flow: check if fully approved
            if ($approvable->isFullyApproved()) {
                $this->completeFlow($approvable, ApprovalDecision::Approved, $step);

                return;
            }

            // Sequential flow: request approval for next step
            if ($flow->is_sequential) {
                event(new ApprovalRequested($approvable, $step + 1));
            }
        }
    }

    /**
     * Complete the approval flow.
     *
     * @param  Model&HasApprovals&Approvable  $approvable
     */
    protected function completeFlow(Model $approvable, ApprovalDecision $decision, int $step): void
    {
        // Call the model's completion hook
        $approvable->onApprovalComplete($decision, $step);

        // Dispatch flow completed event
        event(new ApprovalFlowCompleted($approvable, $decision));
    }

    /**
     * Get pending approvals for a user across all approvable types.
     *
     * @return Collection<int, Model&HasApprovals>
     */
    public function getPendingApprovalsForUser(User $user, ?string $approvableType = null): Collection
    {
        // This is a placeholder - actual implementation would need to query
        // each approvable type and filter by user's permissions
        // For now, this serves as the interface definition
        return collect();
    }
}
