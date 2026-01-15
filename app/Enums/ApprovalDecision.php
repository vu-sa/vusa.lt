<?php

namespace App\Enums;

/**
 * Approval decisions for the approval system.
 *
 * @typescript
 */
enum ApprovalDecision: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /**
     * Check if the decision is positive (approving).
     */
    public function isPositive(): bool
    {
        return $this === self::Approved;
    }

    /**
     * Check if the decision is negative (rejecting or cancelling).
     */
    public function isNegative(): bool
    {
        return $this === self::Rejected || $this === self::Cancelled;
    }

    /**
     * Check if the decision terminates the approval flow.
     */
    public function isTerminating(): bool
    {
        return $this === self::Rejected || $this === self::Cancelled;
    }

    /**
     * Get the localized label for the decision.
     */
    public function label(): string
    {
        return match ($this) {
            self::Approved => __('Patvirtinta'),
            self::Rejected => __('Atmesta'),
            self::Cancelled => __('Atšaukta'),
        };
    }

    /**
     * Get the tag type for UI display (matches existing tag styling).
     */
    public function tagType(): string
    {
        return match ($this) {
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'warning',
        };
    }

    /**
     * Get the icon key for UI display.
     */
    public function iconKey(): string
    {
        return match ($this) {
            self::Approved => 'CHECKMARK',
            self::Rejected => 'DISMISS',
            self::Cancelled => 'ARROW_UNDO',
        };
    }

    /**
     * Get all available decisions as options for forms.
     *
     * @return array<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $decision) => [
                'value' => $decision->value,
                'label' => $decision->label(),
            ],
            self::cases()
        );
    }
}
