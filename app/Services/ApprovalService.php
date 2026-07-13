<?php

namespace App\Services;

use App\Contracts\Approvable;
use App\Enums\ApprovalDecision;
use App\Events\ApprovalDecisionMade;
use App\Events\ApprovalFlowCompleted;
use App\Events\ApprovalRequested;
use App\Models\Approval;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-type ApprovableModel = \Illuminate\Database\Eloquent\Model&\App\Contracts\Approvable
 */
class ApprovalService
{
    /**
     * Request approval for a model.
     * Dispatches ApprovalRequested event which triggers task creation and notifications.
     *
     * @param  ApprovableModel  $approvable
     */
    public function requestApproval(Model&Approvable $approvable): void
    {
        event(new ApprovalRequested($approvable));
    }

    /**
     * Create an approval decision for a model.
     *
     * @param  ApprovableModel  $approvable
     *
     * @throws \InvalidArgumentException If user cannot approve at the given step
     */
    public function approve(
        Model&Approvable $approvable,
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
        if (! $approvable->isDecisionAllowed($decision)) {
            throw new \InvalidArgumentException(__('Šis veiksmas negalimas dabartinėje būsenoje.'));
        }

        $approval = DB::transaction(function () use ($approvable, $user, $decision, $notes, $step) {
            $approval = Approval::create([
                'approvable_type' => get_class($approvable),
                'approvable_id' => $approvable->getKey(),
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
     * Drive an approvable all the way to its final approved state in one action.
     *
     * Approving advances a reservation resource one step along created → reserved → lent →
     * returned. Housekeeping — closing out a stale request nobody ever picked up — would otherwise
     * mean clicking through every step. This fast-forwards the chain, but does not bypass it: each
     * transition still goes through approve(), so authorization, state validation, the Approval
     * audit trail and the notifications all still happen.
     *
     * @param  ApprovableModel  $approvable
     * @return Collection<int, Approval> The approvals recorded, in order. Empty if nothing moved.
     *
     * @throws \InvalidArgumentException If the user may not approve the item.
     */
    public function fastForward(
        Model&Approvable $approvable,
        User $user,
        ?string $notes = null,
        int $maxSteps = 5
    ): Collection {
        $approvals = collect();

        // Bounded: the chain is 3 transitions long, the cap only guards against a future cycle.
        for ($i = 0; $i < $maxSteps; $i++) {
            if (! $approvable->isDecisionAllowed(ApprovalDecision::Approved)) {
                break;
            }

            $approvals->push(
                $this->approve($approvable, $user, ApprovalDecision::Approved, $notes)
            );

            $approvable->refresh();
        }

        return $approvals;
    }

    /**
     * Bulk approve multiple models.
     *
     * @param  Collection<int, ApprovableModel>  $approvables
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
     * @param  ApprovableModel  $approvable
     */
    protected function handlePostDecision(Model&Approvable $approvable, ApprovalDecision $decision, int $step): void
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
     * @param  ApprovableModel  $approvable
     */
    protected function completeFlow(Model&Approvable $approvable, ApprovalDecision $decision, int $step): void
    {
        // Call the model's completion hook
        $approvable->onApprovalComplete($decision, $step);

        // Dispatch flow completed event
        event(new ApprovalFlowCompleted($approvable, $decision));
    }

    /**
     * Get pending approvals for a user across all approvable types.
     *
     * @return Collection<int, Model>
     */
    public function getPendingApprovalsForUser(User $user, ?string $approvableType = null): Collection
    {
        // This is a placeholder - actual implementation would need to query
        // each approvable type and filter by user's permissions
        // For now, this serves as the interface definition
        return collect();
    }
}
