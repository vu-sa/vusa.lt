<?php

namespace App\Events;

use App\Models\Meeting;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched after a meeting is fully created with all relationships.
 *
 * This event fires after:
 * - Meeting model is created
 * - Institutions are attached
 * - Initial agenda items are created (if any)
 *
 * Use this event instead of eloquent.created when you need access to
 * meeting relationships.
 */
class MeetingFullyCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Meeting $meeting
    ) {}
}
