<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by the public feedback widget and the admin one. Both only queue a mail to the IT
 * address, so there is nothing to authorize beyond the route's own middleware.
 */
class SendFeedbackRequest extends FormRequest
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
            'feedback' => 'required|string',
            'href' => ['nullable', 'string'],
            'selectedText' => ['nullable', 'string'],
            'anonymous' => ['nullable', 'boolean'],
        ];
    }
}
