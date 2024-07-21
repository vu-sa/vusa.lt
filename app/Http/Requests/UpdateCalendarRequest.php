<?php

namespace App\Http\Requests;

use App\Models\Calendar;
use Illuminate\Support\Carbon;

class UpdateCalendarRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', [Calendar::class, $this->calendar, $this->authorizer]);
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'date' => Carbon::parse($this->date / 1000)->format('Y-m-d H:i'),
        ]);

        if ($this->end_date !== null) {
            $this->merge([
                'end_date' => Carbon::parse($this->end_date / 1000)->format('Y-m-d H:i'),
            ]);
        }
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
