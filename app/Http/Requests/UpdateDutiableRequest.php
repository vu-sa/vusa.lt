<?php

namespace App\Http\Requests;

use App\Models\Duty;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateDutiableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', [Duty::class, request('duty'), $this->authorizer]);
;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'start_date' => Carbon::createFromTimestampMs($this->input('start_date'), 'Europe/Vilnius'),
            'end_date' => Carbon::createFromTimestampMs($this->input('end_date'), 'Europe/Vilnius'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'extra_attributes' => 'nullable|array',
        ];
    }
}
