<?php

namespace App\Enums;

/**
 * Lifecycle of a survey in vusa.lt.
 *
 * Deliberately a plain backed enum rather than spatie/laravel-model-states, which
 * ReservationResource uses. That model has a genuinely branching lifecycle (reserved →
 * lent → returned, each with its own guards); a survey moves in one direction, and the
 * only real gate — approval — is already enforced by the Approvable contract.
 *
 * Draft ─▶ PendingApproval ─▶ Approved ─▶ Active ─▶ Closed
 *                          └─▶ Rejected
 *
 * Approved means "cleared to publish"; the queued job moves it to Active once LimeSurvey
 * has accepted and activated the survey.
 *
 * @typescript
 */
enum SurveyStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Active = 'active';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('surveys.status.draft'),
            self::PendingApproval => __('surveys.status.pending_approval'),
            self::Approved => __('surveys.status.approved'),
            self::Rejected => __('surveys.status.rejected'),
            self::Active => __('surveys.status.active'),
            self::Closed => __('surveys.status.closed'),
        };
    }

    /**
     * Badge colour, matching the convention in ApprovalDecision::tagType().
     */
    public function tagType(): string
    {
        return match ($this) {
            self::Draft => 'default',
            self::PendingApproval => 'warning',
            self::Approved, self::Active => 'success',
            self::Rejected => 'danger',
            self::Closed => 'info',
        };
    }

    /**
     * Whether the questions and settings may still be edited.
     *
     * Once a survey exists in LimeSurvey its structure is locked there, so vusa.lt must
     * stop pretending it can be changed.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
