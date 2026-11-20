<?php

namespace App\Http\Requests\MailingLists;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncMailingListRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'preview_token' => ['nullable', 'string', 'max:4096'],
        ];
    }
}
