<?php

namespace App\Enums;

enum VoteValue: string
{
    case Positive = 'positive';
    case Negative = 'negative';
    case Neutral = 'neutral';

    /**
     * Get the localized label for decision (outcome).
     */
    public function decisionLabel(string $locale = 'lt'): string
    {
        return match ($this) {
            self::Positive => $locale === 'en' ? 'Approved' : 'Priimtas',
            self::Negative => $locale === 'en' ? 'Rejected' : 'Nepriimtas',
            self::Neutral => $locale === 'en' ? 'Abstained' : 'Susilaikyta',
        };
    }

    /**
     * Get the localized label for student vote.
     */
    public function studentVoteLabel(string $locale = 'lt'): string
    {
        return match ($this) {
            self::Positive => $locale === 'en' ? 'Approved' : 'Pritarė',
            self::Negative => $locale === 'en' ? 'Opposed' : 'Nepritarė',
            self::Neutral => $locale === 'en' ? 'Abstained' : 'Susilaikė',
        };
    }

    /**
     * Get the localized label for student benefit.
     */
    public function studentBenefitLabel(string $locale = 'lt'): string
    {
        return match ($this) {
            self::Positive => $locale === 'en' ? 'Favorable' : 'Palanku',
            self::Negative => $locale === 'en' ? 'Unfavorable' : 'Nepalanku',
            self::Neutral => $locale === 'en' ? 'Neutral' : 'Neutralu',
        };
    }

    /**
     * Get the badge color for this value.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::Positive => 'green',
            self::Negative => 'red',
            self::Neutral => 'gray',
        };
    }

    /**
     * Get all values as an array for frontend.
     */
    public static function toArray(string $locale = 'lt'): array
    {
        return array_map(
            fn (self $value) => [
                'value' => $value->value,
                'decisionLabel' => $value->decisionLabel($locale),
                'studentVoteLabel' => $value->studentVoteLabel($locale),
                'studentBenefitLabel' => $value->studentBenefitLabel($locale),
                'badgeColor' => $value->badgeColor(),
            ],
            self::cases()
        );
    }

    /**
     * Get default values for consensus votes.
     * Consensus means approved unanimously without formal voting,
     * so all values are positive.
     *
     * @return array{decision: string, student_vote: string, student_benefit: string, is_consensus: bool}
     */
    public static function consensusDefaults(): array
    {
        return [
            'decision' => self::Positive->value,
            'student_vote' => self::Positive->value,
            'student_benefit' => self::Positive->value,
            'is_consensus' => true,
        ];
    }
}
