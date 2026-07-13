<?php

namespace Database\Factories\Concerns;

/**
 * Helpers for building Spatie-translatable attribute values in factories.
 *
 * Translatable models store each field as a `{lt: ..., en: ...}` array. Use these
 * helpers instead of hand-writing the locale array literals in every factory.
 */
trait HasTranslatableFactory
{
    /**
     * Build a translatable attribute value for both locales.
     *
     * Pass explicit values when the content matters; omit a locale to fall back
     * to a locale-appropriate fake sentence.
     *
     * @return array{lt: string, en: string}
     */
    protected function translatable(?string $lt = null, ?string $en = null): array
    {
        return [
            'lt' => $lt ?? fake('lt_LT')->sentence(),
            'en' => $en ?? fake('en_US')->sentence(),
        ];
    }

    /**
     * Build a translatable short label (a few title-cased words) in both locales.
     *
     * @return array{lt: string, en: string}
     */
    protected function translatableWords(int $count = 2): array
    {
        return [
            'lt' => ucwords(fake('lt_LT')->words($count, true)),
            'en' => ucwords(fake('en_US')->words($count, true)),
        ];
    }
}
