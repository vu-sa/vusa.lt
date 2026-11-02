<?php

namespace App\Enums;

/**
 * What an institution's sittings are called. Naming only: whether votes and agenda tasks apply
 * stays with the governance scope.
 *
 * Declared on an institution type as `extra_attributes['meeting_kind']`, overridable per
 * institution (`institutions.meeting_kind`) — see Institution::resolvedMeetingKind().
 */
enum MeetingKind: string
{
    /** Formal sitting of a governing body: Senatas, Taryba, Parlamentas. */
    case Sitting = 'sitting';

    /** Members' gathering: padalinių, PKP and club susirinkimai. */
    case Assembly = 'assembly';

    /** Informal representation without a formal agenda, e.g. with a dean. */
    case Meeting = 'meeting';

    case Conference = 'conference';

    /** Lowercase, as it ends a meeting title ("2026 rugsėjo 12 d. susirinkimas"). */
    public function noun(string $locale = 'lt'): string
    {
        return match ($this) {
            self::Sitting => $locale === 'en' ? 'meeting' : 'posėdis',
            self::Assembly => $locale === 'en' ? 'assembly' : 'susirinkimas',
            self::Meeting => $locale === 'en' ? 'meeting' : 'susitikimas',
            self::Conference => $locale === 'en' ? 'conference' : 'konferencija',
        };
    }

    public function label(string $locale = 'lt'): string
    {
        return mb_ucfirst($this->noun($locale));
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function toArray(string $locale = 'lt'): array
    {
        return array_map(
            fn (self $kind) => ['value' => $kind->value, 'label' => $kind->label($locale)],
            self::cases()
        );
    }
}
