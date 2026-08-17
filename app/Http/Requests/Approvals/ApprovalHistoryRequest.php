<?php

namespace App\Http\Requests\Approvals;

use Illuminate\Contracts\Validation\ValidationRule;

class ApprovalHistoryRequest extends ApprovableRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'approvable_id' => 'required|string',
        ]);
    }
}
