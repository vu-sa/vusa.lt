<?php

namespace App\Enums;

/** Naming defaults to the institution type and can be overridden per meeting. */
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

    public function jointNoun(string $locale = 'lt'): string
    {
        if ($locale === 'en') {
            return 'joint '.$this->noun($locale);
        }

        return ($this === self::Conference ? 'jungtinė ' : 'jungtinis ').$this->noun($locale);
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
