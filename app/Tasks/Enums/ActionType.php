<?php

namespace App\Tasks\Enums;

/**
 * Defines the type of action a task represents.
 *
 * Tasks can be either manually completable by users or auto-completed
 * by the system when certain conditions are met.
 */
enum ActionType: string
{
    /**
     * Manual task - can be completed by any assigned user at any time.
     */
    case Manual = 'manual';

    /**
     * Approval task - auto-completes when approval decision is made.
     * Created when an approvable item enters approval flow.
     */
    case Approval = 'approval';

    /**
     * Pickup task - auto-completes when resource state changes to Lent.
     * Created when a reservation resource is approved and ready for pickup.
     */
    case Pickup = 'pickup';

    /**
     * Return task - auto-completes when resource state changes to Returned.
     * Created when a resource is lent and needs to be returned.
     */
    case Return = 'return';

    /**
     * Agenda creation task - auto-completes when first agenda item is created.
     * Created when a meeting is created without agenda items.
     */
    case AgendaCreation = 'agenda_creation';

    /**
     * Agenda completion task - auto-completes when all agenda items are filled.
     * Created when agenda items exist but need completion.
     */
    case AgendaCompletion = 'agenda_completion';

    /**
     * Periodicity gap task - auto-completes when a meeting or check-in is created.
     * Created when an institution is approaching its meeting periodicity threshold with no scheduled meeting.
     * Users can resolve by scheduling a meeting or reporting a check-in period.
     */
    case PeriodicityGap = 'periodicity_gap';

    /**
     * Whether this task type can be manually completed by users.
     */
    public function canBeManuallyCompleted(): bool
    {
        return match ($this) {
            self::Manual => true,
            self::Approval, self::Pickup, self::Return, self::AgendaCreation, self::AgendaCompletion, self::PeriodicityGap => false,
        };
    }

    /**
     * Whether this task auto-completes based on system events.
     */
    public function isAutoCompletable(): bool
    {
        return ! $this->canBeManuallyCompleted();
    }

    /**
     * Get a human-readable label for the action type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Manual => __('Manual'),
            self::Approval => __('Approval'),
            self::Pickup => __('Pickup'),
            self::Return => __('Return'),
            self::AgendaCreation => __('Agenda Creation'),
            self::AgendaCompletion => __('Agenda Completion'),
            self::PeriodicityGap => __('Periodicity Gap'),
        };
    }

    /**
     * Get the icon name for frontend display.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Manual => 'clipboard-check',
            self::Approval => 'shield-check',
            self::Pickup => 'package',
            self::Return => 'package-check',
            self::AgendaCreation => 'list-plus',
            self::AgendaCompletion => 'list-checks',
            self::PeriodicityGap => 'calendar-clock',
        };
    }

    /**
     * Get color scheme for the action type.
     */
    public function color(): string
    {
        return match ($this) {
            self::Manual => 'zinc',
            self::Approval => 'blue',
            self::Pickup => 'amber',
            self::Return => 'emerald',
            self::AgendaCreation => 'indigo',
            self::AgendaCompletion => 'violet',
            self::PeriodicityGap => 'orange',
        };
    }
}
