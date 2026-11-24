<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class AtstovavimasGanttRequest extends AtstovavimasTenantRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'institution_ids' => ['sometimes', 'array', 'list', 'max:100'],
            'institution_ids.*' => ['required', 'string', 'ulid', 'distinct:ignore_case'],
        ];
    }

    /** @return list<string>|null */
    public function institutionIds(): ?array
    {
        $ids = $this->validated('institution_ids');
        if ($ids === null) {
            return null;
        }

        $ids = array_map(strtoupper(...), $ids);
        sort($ids, SORT_STRING);

        return $ids;
    }
}
