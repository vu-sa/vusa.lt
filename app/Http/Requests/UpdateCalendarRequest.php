<?php

namespace App\Http\Requests;

use App\Rules\SoftDeleteRules;

class UpdateCalendarRequest extends CalendarRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'event_type_id' => ['nullable', 'integer', SoftDeleteRules::existsLive('event_types')],
        ]);
    }
}
