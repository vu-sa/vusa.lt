<?php

namespace App\Tasks\Subscribers;

use App\Models\InstitutionCheckIn;
use App\Tasks\Handlers\PeriodicityGapTaskHandler;
use Illuminate\Events\Dispatcher;

/**
 * Event subscriber for InstitutionCheckIn task operations.
 *
 * Completes periodicity gap tasks when check-ins are created,
 * allowing representatives to address periodicity warnings without
 * scheduling a full meeting.
 */
class InstitutionCheckInTaskSubscriber
{
    public function __construct(
        protected PeriodicityGapTaskHandler $periodicityGapHandler,
    ) {}

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            'eloquent.created: '.InstitutionCheckIn::class,
            [self::class, 'handleCheckInCreated']
        );
    }

    /**
     * Handle check-in created event.
     * Completes any pending periodicity gap task for the institution.
     */
    public function handleCheckInCreated(InstitutionCheckIn $checkIn): void
    {
        $checkIn->load('institution');

        if (! $checkIn->institution) {
            return;
        }

        $this->periodicityGapHandler->completeForInstitution(
            institution: $checkIn->institution,
            reason: __('tasks.periodicity_gap.completed_checkin_created'),
        );
    }
}
