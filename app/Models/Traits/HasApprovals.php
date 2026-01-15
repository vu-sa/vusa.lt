<?php

namespace App\Models\Traits;

use App\Enums\ApprovalDecision;
use App\Models\Approval;
use App\Models\ApprovalFlow;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Provides approval functionality to models.
 *
 * Models using this trait should implement the Approvable contract
 * for full approval flow support.
 */
trait HasApprovals
{
    /**
     * Get all approvals for this model.
     */
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    /**
     * Get the latest approval for this model.
     */
    public function latestApproval(): ?Approval
    {
        return $this->approvals()->latest()->first();
    }

    /**
     * Get approvals for a specific step.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Approval>
     */
    public function approvalsForStep(int $step)
    {
        return $this->approvals()->forStep($step)->get();
    }

    /**
     * Get the current step in the approval flow.
     * Returns 1 if no approvals exist yet.
     */
    public function currentApprovalStep(): int
    {
        $flow = $this->getApprovalFlow();

        if (! $flow) {
            return 1;
        }

        // For sequential flows, find the highest completed step
        if ($flow->is_sequential) {
            for ($step = 1; $step <= $flow->total_steps; $step++) {
                if (! $this->isStepComplete($step)) {
                    return $step;
                }
            }

            return $flow->total_steps;
        }

        // For parallel flows, always return 1 (all steps happen at once)
        return 1;
    }

    /**
     * Check if a specific step is complete.
     */
    public function isStepComplete(int $step): bool
    {
        $flow = $this->getApprovalFlow();

        if (! $flow) {
            // No flow defined, check for any approval
            return $this->approvals()->forStep($step)->approved()->exists();
        }

        $requiredCount = $flow->getRequiredCountForStep($step);
        $approvedCount = $this->approvals()->forStep($step)->approved()->count();

        return $approvedCount >= $requiredCount;
    }

    /**
     * Check if the entire approval flow is complete.
     */
    public function isFullyApproved(): bool
    {
        $flow = $this->getApprovalFlow();

        if (! $flow) {
            // No flow defined, check for any approval
            return $this->approvals()->approved()->exists();
        }

        for ($step = 1; $step <= $flow->total_steps; $step++) {
            if (! $this->isStepComplete($step)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the approval has been rejected.
     */
    public function isRejected(): bool
    {
        return $this->approvals()->rejected()->exists();
    }

    /**
     * Check if the approval has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->approvals()->cancelled()->exists();
    }

    /**
     * Check if approval is still pending (not completed, rejected, or cancelled).
     */
    public function isPendingApproval(): bool
    {
        return ! $this->isFullyApproved() && ! $this->isRejected() && ! $this->isCancelled();
    }

    /**
     * Check if a user can approve this model at the given step.
     *
     * @param  \App\Enums\ApprovalDecision|null  $decision  The decision being made (for owner-specific restrictions)
     */
    public function canBeApprovedBy(User $user, ?int $step = null, $decision = null): bool
    {
        $step = $step ?? $this->currentApprovalStep();

        // Check if user has already approved this step
        if ($this->approvals()->forStep($step)->where('user_id', $user->id)->exists()) {
            return false;
        }

        // Check if step is already complete
        if ($this->isStepComplete($step)) {
            return false;
        }

        // Check if approval is terminated
        if ($this->isRejected() || $this->isCancelled()) {
            return false;
        }

        // Delegate to model's implementation for permission checking
        if (method_exists($this, 'getApproversForStep')) {
            return $this->getApproversForStep($step)->contains('id', $user->id);
        }

        return true;
    }

    /**
     * Get the approval flow for this model.
     * Should be overridden by models that use specific flows.
     */
    public function getApprovalFlow(): ?ApprovalFlow
    {
        // Try to find a flow attached to this specific model
        $flow = ApprovalFlow::query()
            ->where('flowable_type', get_class($this))
            ->where('flowable_id', $this->id)
            ->first();

        if ($flow) {
            return $flow;
        }

        // Fall back to global flow for this model type
        return ApprovalFlow::query()
            ->where('flowable_type', get_class($this))
            ->whereNull('flowable_id')
            ->first();
    }

    /**
     * Get the final decision for this model.
     * Returns the terminating decision (approved at final step, rejected, or cancelled).
     */
    public function getFinalDecision(): ?ApprovalDecision
    {
        if ($this->isRejected()) {
            return ApprovalDecision::Rejected;
        }

        if ($this->isCancelled()) {
            return ApprovalDecision::Cancelled;
        }

        if ($this->isFullyApproved()) {
            return ApprovalDecision::Approved;
        }

        return null;
    }
}
