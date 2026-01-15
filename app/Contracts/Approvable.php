<?php

namespace App\Contracts;

use App\Enums\ApprovalDecision;
use App\Models\ApprovalFlow;
use Illuminate\Support\Collection;

/**
 * Contract for models that support the approval system.
 *
 * Models implementing this contract can be approved through the ApprovalService
 * and will receive callbacks when approvals complete.
 */
interface Approvable
{
    /**
     * Called when an approval decision is made that completes a step or flow.
     *
     * This is the main hook for models to react to approval decisions.
     * For example, ReservationResource uses this to transition states.
     */
    public function onApprovalComplete(ApprovalDecision $decision, int $step): void;

    /**
     * Get the users who can approve this model at the given step.
     *
     * @return Collection<int, \App\Models\User>
     */
    public function getApproversForStep(int $step): Collection;

    /**
     * Get the approval flow configuration for this model.
     *
     * Returns null if using a simple single-step approval.
     */
    public function getApprovalFlow(): ?ApprovalFlow;

    /**
     * Get the name/title of this approvable for display in notifications.
     */
    public function getApprovalDisplayName(): string;

    /**
     * Get the URL for viewing this approvable item.
     */
    public function getApprovalUrl(): string;

    /**
     * Check if a specific decision is allowed for the current state.
     *
     * This should return false if the state transition would not be valid.
     * For example, a "lent" resource cannot be rejected.
     */
    public function isDecisionAllowed(ApprovalDecision $decision): bool;
}
