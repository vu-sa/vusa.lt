<?php

namespace App\Http\Requests;

class UpdateCalendarRequest extends CalendarRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->calendar);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'category' => 'nullable',
        ]);
    }
}
