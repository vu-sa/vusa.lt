<?php

namespace App\Console\Commands;

use App\Models\Approval;
use App\Models\ApprovalFlow;
use App\Models\User;
use App\Notifications\ApprovalEscalationNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Send escalation reminders for overdue approval requests.
 *
 * This command runs weekly and notifies approvers who have pending
 * approval requests that are past the escalation threshold.
 */
class EscalateOverdueApprovals extends Command
{
    protected $signature = 'approvals:escalate-overdue';

    protected $description = 'Send escalation reminders for overdue approval requests';

    public function handle(): int
    {
        // Get all approval flows with escalation configured
        $flows = ApprovalFlow::query()
            ->whereNotNull('escalation_days')
            ->get();

        $escalatedCount = 0;

        foreach ($flows as $flow) {
            $escalatedCount += $this->escalateFlowApprovals($flow);
        }

        $this->info("Escalated {$escalatedCount} overdue approval requests.");

        return self::SUCCESS;
    }

    /**
     * Find and escalate overdue approvals for a specific flow.
     */
    protected function escalateFlowApprovals(ApprovalFlow $flow): int
    {
        $approvableType = $flow->flowable_type;

        if (! $approvableType) {
            return 0;
        }

        $escalationThreshold = Carbon::now()->subDays($flow->escalation_days);

        // Find approvables that need escalation:
        // - Have no approvals yet, and were created before threshold
        // - OR have approvals but not complete, and latest approval before threshold
        $pendingApprovables = $this->findPendingApprovables($approvableType, $escalationThreshold);

        $count = 0;

        foreach ($pendingApprovables as $approvable) {
            // Skip if fully approved, rejected, or cancelled
            if (method_exists($approvable, 'isFullyApproved') && $approvable->isFullyApproved()) {
                continue;
            }
            if (method_exists($approvable, 'isRejected') && $approvable->isRejected()) {
                continue;
            }
            if (method_exists($approvable, 'isCancelled') && $approvable->isCancelled()) {
                continue;
            }

            // Get approvers for current step
            $step = method_exists($approvable, 'currentApprovalStep')
                ? $approvable->currentApprovalStep()
                : 1;

            $approvers = method_exists($approvable, 'getApproversForStep')
                ? $approvable->getApproversForStep($step)
                : collect();

            if ($approvers->isEmpty()) {
                continue;
            }

            // Send escalation notification
            foreach ($approvers as $approver) {
                if ($approver instanceof User) {
                    $approver->notify(new ApprovalEscalationNotification($approvable));
                }
            }

            $count++;

            if (method_exists($approvable, 'getApprovalDisplayName')) {
                $this->info("Escalated: {$approvable->getApprovalDisplayName()}");
            }
        }

        return $count;
    }

    /**
     * Find approvables of a given type that are pending and overdue.
     */
    protected function findPendingApprovables(string $approvableType, Carbon $threshold)
    {
        // First, find approvables with no approvals that are old
        $noApprovalsQuery = $approvableType::query()
            ->where('created_at', '<', $threshold)
            ->whereDoesntHave('approvals');

        // Then, find approvables with approvals but not fully approved
        // where the latest approval is old
        $staleApprovalsQuery = $approvableType::query()
            ->whereHas('approvals', function ($query) use ($threshold) {
                $query->where('created_at', '<', $threshold);
            })
            ->whereDoesntHave('approvals', function ($query) use ($threshold) {
                $query->where('created_at', '>=', $threshold);
            });

        // Combine results
        return $noApprovalsQuery->get()->merge($staleApprovalsQuery->get())->unique('id');
    }
}
