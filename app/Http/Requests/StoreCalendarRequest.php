<?php

namespace App\Http\Requests;

use App\Models\Calendar;

class StoreCalendarRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [Calendar::class, $this->authorizer]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'tenant_id' => 'required|integer',
            'end_date' => 'nullable|date|after:date',
            'title' => 'required',
            'description' => 'required',
            'location' => 'nullable',
            'url' => 'nullable',
            'category' => 'nullable',
            'extra_attributes' => 'nullable',
        ];
    }
}
