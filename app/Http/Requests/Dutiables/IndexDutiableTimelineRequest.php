<?php

namespace App\Http\Requests\Dutiables;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The standalone page's only input is which institution to open on. Authorization lives
 * in the controller, which has to check the resolved model rather than the id.
 */
class IndexDutiableTimelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'institution' => ['nullable', 'ulid', Rule::exists('institutions', 'id')->whereNull('deleted_at')],
        ];
    }
}
