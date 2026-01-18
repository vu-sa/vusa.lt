<?php

namespace App\Notifications\Subscribers;

use App\Contracts\Approvable;
use App\Events\ApprovalDecisionMade;
use App\Events\ApprovalRequested;
use App\Notifications\ApprovalDecisionNotification;
use App\Notifications\ApprovalRequestedNotification;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Notification;

/**
 * Event subscriber for approval-related notifications.
 *
 * Consolidates notification sending for approval workflow:
 * - Sends ApprovalRequestedNotification when approval is requested
 * - Sends ApprovalDecisionNotification when a decision is made
 *
 * Note: Task creation is handled separately by ApprovalTaskSubscriber.
 */
class ApprovalNotificationSubscriber
{
    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            ApprovalRequested::class,
            [self::class, 'handleApprovalRequested']
        );

        $events->listen(
            ApprovalDecisionMade::class,
            [self::class, 'handleApprovalDecisionMade']
        );
    }

    /**
     * Send notification when approval is requested.
     */
    public function handleApprovalRequested(ApprovalRequested $event): void
    {
        $approvable = $event->approvable;
        $step = $event->step;

        // Ensure the model implements Approvable
        if (! $approvable instanceof Approvable) {
            return;
        }

        // Get approvers for this step
        $approvers = $approvable->getApproversForStep($step);

        if ($approvers->isEmpty()) {
            return;
        }

        // Send notification to all approvers
        Notification::send(
            $approvers,
            new ApprovalRequestedNotification($approvable, $step)
        );
    }

    /**
     * Send notification when approval decision is made.
     */
    public function handleApprovalDecisionMade(ApprovalDecisionMade $event): void
    {
        $approvable = $event->approvable;
        $approval = $event->approval;

        // Ensure the model implements Approvable
        if (! $approvable instanceof Approvable) {
            return;
        }

        // Get decision maker from approval record
        $decisionMaker = $approval->user;

        if (! $decisionMaker) {
            return;
        }

        // Notify the requester (users who created/own the approvable)
        $recipients = $this->getDecisionNotificationRecipients($approvable);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new ApprovalDecisionNotification($approval, $approvable, $decisionMaker)
        );
    }

    /**
     * Get users who should be notified of the decision.
     * Typically the users who created/own the approvable.
     *
     * @param  \Illuminate\Database\Eloquent\Model&Approvable  $approvable
     * @return \Illuminate\Support\Collection<int, \App\Models\User>
     */
    protected function getDecisionNotificationRecipients($approvable)
    {
        // For ReservationResource, notify the reservation's users
        if (method_exists($approvable, 'reservation') && $approvable->reservation) {
            return $approvable->reservation->users ?? collect();
        }

        // For models with users relationship
        if (method_exists($approvable, 'users')) {
            return $approvable->users ?? collect();
        }

        // For models with a single user
        if (method_exists($approvable, 'user') && $approvable->user) {
            return collect([$approvable->user]);
        }

        return collect();
    }
}
