<?php

namespace App\Enums;

/**
 * Which governance world an institution belongs to.
 *
 * Stored on the institution's Type as `extra_attributes['governance_scope']` and inherited down
 * the `types.parent_id` tree — see App\Services\InstitutionScopeResolver.
 */
enum InstitutionScope: string
{
    /** VU SA's own bodies: Parlamentas, Taryba, Revizijos komisija, padaliniai, valdybos, PKP. */
    case Vusa = 'vusa';

    /** VU bodies where VU SA delegates student representatives. */
    case University = 'vu';

    case National = 'national';

    case International = 'international';

    public function label(string $locale = 'lt'): string
    {
        return match ($this) {
            self::Vusa => $locale === 'en' ? 'VU SA body' : 'VU SA darinys',
            self::University => $locale === 'en' ? 'VU body' : 'VU organas',
            self::National => $locale === 'en' ? 'National body' : 'Nacionalinis organas',
            self::International => $locale === 'en' ? 'International body' : 'Tarptautinis organas',
        };
    }

    /**
     * True only for VU SA's own bodies, where the representatives *are* the organisation.
     */
    public function isInternal(): bool
    {
        return $this === self::Vusa;
    }

    /**
     * VU, national and international bodies alike are external: VU SA delegates representatives
     * into them, so the student-perspective vote fields apply.
     */
    public function isExternal(): bool
    {
        return ! $this->isInternal();
    }

    /**
     * @return array<int, array{value: string, label: string, isExternal: bool}>
     */
    public static function toArray(string $locale = 'lt'): array
    {
        return array_map(
            fn (self $scope) => [
                'value' => $scope->value,
                'label' => $scope->label($locale),
                'isExternal' => $scope->isExternal(),
            ],
            self::cases()
        );
    }
}
