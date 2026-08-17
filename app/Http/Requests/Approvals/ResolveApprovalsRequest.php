<?php

namespace App\Http\Requests\Approvals;

use Illuminate\Contracts\Validation\ValidationRule;

class ResolveApprovalsRequest extends ApprovableRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'approvable_ids' => 'required|array|min:1',
            'approvable_ids.*' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);
    }
}
