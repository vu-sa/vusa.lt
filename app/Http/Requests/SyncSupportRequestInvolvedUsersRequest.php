<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncSupportRequestInvolvedUsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $supportRequest = $this->route('support_request') ?? $this->route('supportRequest');

        return $this->user()?->can('assign', $supportRequest) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'involved_users' => ['present', 'array', 'max:20'],
            'involved_users.*' => ['string', 'distinct', Rule::exists('users', 'id')],
        ];
    }
}
