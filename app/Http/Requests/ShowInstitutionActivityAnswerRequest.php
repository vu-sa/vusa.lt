<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\DropsInvalidOptionalQuery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The answer buttons in the email preselect a choice; an unknown one just preselects nothing.
 */
class ShowInstitutionActivityAnswerRequest extends FormRequest
{
    use DropsInvalidOptionalQuery;

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
            'answer' => ['nullable', 'string', 'max:32'],
        ];
    }
}
