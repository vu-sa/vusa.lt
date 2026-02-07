<?php

namespace App\Notifications\Subscribers;

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

        // Get approvers for this step
        /** @var \App\Contracts\Approvable&\Illuminate\Database\Eloquent\Model $approvable */
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

        // Get decision maker from approval record
        $decisionMaker = $approval->user;

        // Notify the requester (users who created/own the approvable)
        /** @var \App\Contracts\Approvable&\Illuminate\Database\Eloquent\Model $approvable */
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
     * @param  \Illuminate\Database\Eloquent\Model&\App\Contracts\Approvable  $approvable
     * @return \Illuminate\Support\Collection<int, \App\Models\User>
     */
    protected function getDecisionNotificationRecipients($approvable)
    {
        // For ReservationResource, notify the reservation's users
        if (method_exists($approvable, 'reservation')) {
            $reservation = $approvable->reservation()->first();

            return $reservation ? $reservation->users : collect();
        }

        // For models with users relationship
        if (method_exists($approvable, 'users')) {
            return $approvable->users()->get();
        }

        // For models with a single user
        if (method_exists($approvable, 'user')) {
            $user = $approvable->user()->first();

            return $user ? collect([$user]) : collect();
        }

        return collect();
    }
}
