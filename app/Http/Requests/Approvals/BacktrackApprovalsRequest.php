<?php

namespace App\Http\Requests\Approvals;

use App\Enums\ModelEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class BacktrackApprovalsRequest extends ApprovableRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'approvable_type' => ['required', Rule::in([ModelEnum::RESERVATION_RESOURCE->value])],
            'approvable_ids' => ['required', 'array', 'min:1'],
            'approvable_ids.*' => ['required', 'string', 'distinct'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
