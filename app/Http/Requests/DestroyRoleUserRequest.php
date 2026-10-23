<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DestroyRoleUserRequest extends FormRequest
{
    /**
     * Roles held by a person directly are legacy; only a super admin cleans them up.
     */
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'acknowledge_access_change' => ['sometimes', 'boolean'],
        ];
    }
}
