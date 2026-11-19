<?php

namespace App\Http\Requests\MailingLists;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMailingListExclusionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('mailing_list_exclusions', 'email')->where('mailing_list_id', $this->route('mailingList')->id),
            ],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
