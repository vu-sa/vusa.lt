<?php

namespace App\Http\Requests\Concerns;

use App\Actions\Responsibilities\AssignableDutyTypes;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesDutyTypeAssignments
{
    /** @param list<int> $current */
    protected function authorizeDutyTypeChanges(Validator $validator, array $current = []): void
    {
        if ($validator->errors()->has('types') || $validator->errors()->has('types.*')) {
            return;
        }

        abort_unless(AssignableDutyTypes::allowsChanges($this->user(), $current, array_map(intval(...), (array) $this->input('types', []))),
            403, __('types.assignment_forbidden'));
    }
}
