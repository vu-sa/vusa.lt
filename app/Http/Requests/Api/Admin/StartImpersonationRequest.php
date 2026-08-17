<?php

namespace App\Http\Requests\Api\Admin;

use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * See ImpersonateSearchRequest — the super-admin guard lives in the controller.
 */
class StartImpersonationRequest extends FormRequest
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
            'user_id' => ['required', SoftDeleteRules::existsLive('users')],
        ];
    }
}
