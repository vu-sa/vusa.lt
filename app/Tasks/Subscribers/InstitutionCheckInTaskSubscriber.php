<?php

namespace App\Tasks\Subscribers;

use App\Models\InstitutionCheckIn;
use App\Tasks\Handlers\PeriodicityGapTaskHandler;
use Illuminate\Events\Dispatcher;

/**
 * Completes periodicity gap tasks when check-ins are created.
 */
class InstitutionCheckInTaskSubscriber
{
    public function __construct(
        protected PeriodicityGapTaskHandler $periodicityGapHandler,
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            'eloquent.created: '.InstitutionCheckIn::class,
            [self::class, 'handleCheckInCreated']
        );
    }

    public function handleCheckInCreated(InstitutionCheckIn $checkIn): void
    {
        $checkIn->load('institution');

        $this->periodicityGapHandler->completeForInstitution(
            institution: $checkIn->institution,
            reason: __('tasks.periodicity_gap.completed_checkin_created'),
        );
    }
}
