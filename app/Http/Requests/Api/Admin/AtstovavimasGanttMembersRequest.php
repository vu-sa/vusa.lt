<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class AtstovavimasGanttMembersRequest extends AtstovavimasGanttRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'institution_ids' => ['sometimes', 'array', 'list', 'min:1', 'max:100'],
        ];
    }
}
