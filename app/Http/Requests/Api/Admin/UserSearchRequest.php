<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\UserSearchPurpose;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserSearchRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => 'required|string|min:2',
            'purpose' => ['required', Rule::enum(UserSearchPurpose::class)],
        ];
    }
}
