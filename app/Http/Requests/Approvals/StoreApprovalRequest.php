<?php

namespace App\Http\Requests\Approvals;

use App\Enums\ApprovalDecision;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;

class StoreApprovalRequest extends ApprovableRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'approvable_id' => 'required|string',
            'decision' => ['required', new Enum(ApprovalDecision::class)],
            'notes' => 'nullable|string|max:1000',
            'step' => 'nullable|integer|min:1',
            'quantity' => 'nullable|integer|min:1',
        ]);
    }
}
