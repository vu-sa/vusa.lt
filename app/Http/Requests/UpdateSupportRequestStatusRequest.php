<?php

namespace App\Http\Requests;

use App\Enums\SupportRequestStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportRequestStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $supportRequest = $this->route('support_request') ?? $this->route('supportRequest');

        return $this->user()?->can('updateStatus', $supportRequest) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(SupportRequestStatus::class)],
        ];
    }
}
