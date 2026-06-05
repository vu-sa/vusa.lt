<?php

namespace App\Http\Requests;

use App\Models\Calendar;

class StoreCalendarRequest extends CalendarRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Calendar::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'category_id' => 'nullable|exists:categories,id',
        ]);
    }
}
