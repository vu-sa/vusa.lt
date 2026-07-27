<?php

namespace App\Rules;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Validation-rule helpers for soft-deletable tables.
 *
 * Laravel's `exists` rule queries the table directly, so it happily matches a
 * soft-deleted row. Anything downstream then resolves the id through Eloquent —
 * which *does* apply the soft-delete scope — and gets null, so validation passing
 * is precisely what turns a rejected input into a 500 further in.
 */
class SoftDeleteRules
{
    /**
     * `exists`, restricted to rows that have not been soft-deleted.
     */
    public static function existsLive(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->whereNull('deleted_at');
    }
}
