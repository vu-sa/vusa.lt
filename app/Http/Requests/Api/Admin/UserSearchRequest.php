<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserSearchRequest extends FormRequest
{
    /**
     * The permissions a picker may scope by. Any other would let a holder of an unrelated
     * `*` permission skip the tenant filter and read every user's full email.
     */
    public const array PERMISSIONS = [
        'duties.update.padalinys',
        'problems.create.padalinys',
        'problems.update.padalinys',
    ];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => 'required|string|min:2',
            // Required: a forgotten parameter must fail rather than scope by some other permission.
            'permission' => ['required', Rule::in(self::PERMISSIONS)],
            'scope' => 'nullable|in:tenant,all',
        ];
    }
}
