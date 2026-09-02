<?php

namespace App\Enums;

enum AgendaItemType: string
{
    case Voting = 'voting';
    case Informational = 'informational';
    case Deferred = 'deferred';
    case Break = 'break';

    /**
     * Get the localized label for the agenda item type.
     */
    public function label(string $locale = 'lt'): string
    {
        return match ($this) {
            self::Voting => $locale === 'en' ? 'Voting' : 'Balsavimas',
            self::Informational => $locale === 'en' ? 'Informational' : 'Informacinis',
            self::Deferred => $locale === 'en' ? 'Deferred' : 'Atidėtas',
            self::Break => $locale === 'en' ? 'Break' : 'Pertrauka',
        };
    }

    /**
     * Get badge color for the type.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::Voting => 'green',
            self::Informational => 'blue',
            self::Deferred => 'gray',
            self::Break => 'amber',
        };
    }

    /**
     * Whether an item of this type is only complete once a vote records an outcome.
     *
     * The single answer to that question: MeetingCompletionService, AgendaCompletionTaskHandler
     * and MeetingController's incomplete-item filter all used to decide it for themselves, and
     * had already drifted apart over `deferred`.
     */
    public function requiresVote(): bool
    {
        return $this === self::Voting;
    }

    /**
     * The types that need no vote to count as complete — an agenda, a pause, a postponement.
     *
     * @return list<string>
     */
    public static function voteFreeValues(): array
    {
        return array_values(array_map(
            fn (self $type): string => $type->value,
            array_filter(self::cases(), fn (self $type): bool => ! $type->requiresVote()),
        ));
    }

    /**
     * Get all types as an array for frontend.
     */
    public static function toArray(string $locale = 'lt'): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label($locale),
                'badgeColor' => $type->badgeColor(),
            ],
            self::cases()
        );
    }
}
