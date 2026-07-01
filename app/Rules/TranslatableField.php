<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a Spatie-translatable field is submitted as a locale array
 * (`{lt: ..., en: ...}`) containing the required locales.
 *
 * By default a single non-empty locale is enough; pass `requireAll: true` to
 * demand a non-empty value for every required locale.
 *
 * Per-locale constraints (max length, uniqueness, etc.) still belong on the
 * dotted sub-keys (`name.lt`, `name.en`); this rule only guards the shape.
 */
class TranslatableField implements ValidationRule
{
    /**
     * @param  array<int, string>  $requiredLocales
     */
    public function __construct(
        protected array $requiredLocales = ['lt', 'en'],
        protected bool $requireAll = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail(__('validation.translatable.array', ['attribute' => $attribute]));

            return;
        }

        $present = array_filter(
            $this->requiredLocales,
            fn (string $locale) => isset($value[$locale]) && is_string($value[$locale]) && trim($value[$locale]) !== '',
        );

        $passes = $this->requireAll
            ? count($present) === count($this->requiredLocales)
            : count($present) > 0;

        if (! $passes) {
            $key = $this->requireAll ? 'validation.translatable.all' : 'validation.translatable.any';

            $fail(__($key, [
                'attribute' => $attribute,
                'locales' => implode(', ', $this->requiredLocales),
            ]));
        }
    }
}
