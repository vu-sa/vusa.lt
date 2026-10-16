<?php

namespace App\Http\Requests\MailingLists;

use App\Enums\MailingListMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMailingListModeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('mailingList'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::enum(MailingListMode::class)],
            'preview_token' => ['required_if:mode,managed', 'nullable', 'string', 'max:4096'],
        ];
    }
}
