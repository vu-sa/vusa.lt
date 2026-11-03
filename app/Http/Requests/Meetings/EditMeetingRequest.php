<?php

namespace App\Http\Requests\Meetings;

use App\Http\Requests\Concerns\DropsInvalidOptionalQuery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The old edit URL forwards its deep-link state to the record page.
 */
class EditMeetingRequest extends FormRequest
{
    use DropsInvalidOptionalQuery;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('meeting')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tab' => ['nullable', 'string', 'max:64'],
            'action' => ['nullable', 'string', 'max:64'],
        ];
    }
}
