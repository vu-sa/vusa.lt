<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Following checks `view` on every institution in the controller; unfollowing only touches the
 * user's own rows, so neither needs a class-level gate here.
 */
class BulkInstitutionFollowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'institution_ids' => ['required', 'array', 'min:1', 'max:200'],
            'institution_ids.*' => ['required', 'ulid', 'distinct', Rule::exists('institutions', 'id')],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function institutionIds(): array
    {
        return array_values(array_map(strval(...), $this->validated('institution_ids')));
    }
}
