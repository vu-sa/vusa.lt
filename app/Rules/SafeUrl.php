<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('validation.url', ['attribute' => $attribute]));

            return;
        }

        $normalized = preg_replace('/[\x00-\x20\x7f]/', '', $value);

        if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $normalized, $matches)
            && ! in_array(strtolower($matches[1]), ['http', 'https', 'mailto', 'tel'], true)) {
            $fail(__('validation.url', ['attribute' => $attribute]));
        }
    }
}
