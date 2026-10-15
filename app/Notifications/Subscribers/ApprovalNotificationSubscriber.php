<?php

namespace App\Notifications\Subscribers;

use App\Contracts\Approvable;
use App\Events\ApprovalRequested;
use App\Notifications\ApprovalRequestedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Notification;

/**
 * Tells approvers a decision is needed. The requester hears the outcome from the state change it
 * causes (ReservationStatusChangedNotification), once and only when the flow completes; a separate
 * decision notice duplicated it and also fired on intermediate steps.
 *
 * Task creation is handled separately by ApprovalTaskSubscriber.
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
            self::handleApprovalRequested(...)
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
        /** @var Approvable&Model $approvable */
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
}
