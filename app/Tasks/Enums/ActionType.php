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
    case Manual = 'manual';
    case Approval = 'approval';
    case Pickup = 'pickup';
    case Return = 'return';
    case AgendaCreation = 'agenda_creation';
    case AgendaCompletion = 'agenda_completion';
    case PeriodicityGap = 'periodicity_gap';

    public function canBeManuallyCompleted(): bool
    {
        return match ($this) {
            self::Manual => true,
            self::Approval, self::Pickup, self::Return, self::AgendaCreation, self::AgendaCompletion, self::PeriodicityGap => false,
        };
    }

    public function isAutoCompletable(): bool
    {
        return ! $this->canBeManuallyCompleted();
    }

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
