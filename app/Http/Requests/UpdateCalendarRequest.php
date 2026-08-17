<?php

namespace App\Http\Requests;

use App\Rules\SoftDeleteRules;

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
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            // The controller persists `category_id`, not `category` — the old key meant the
            // soft-delete guard never ran on update.
            'category_id' => ['nullable', SoftDeleteRules::existsLive('categories')],
        ]);
    }
}
