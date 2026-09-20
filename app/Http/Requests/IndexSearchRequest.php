<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The cross-entity search page. It only reads `q` and `tab`; what a user may find is decided by
 * the scoped Typesense keys at the search layer, and each destination page authorizes itself.
 * An unknown `tab` is not an error: a stale bookmark should land on the search page, not on a 422.
 */
class IndexSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'tab' => ['nullable', 'string', 'max:50'],
        ];
    }
}
