<?php

namespace App\Http\Requests;

use App\Models\Duty;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDutiableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', Duty::find(request('duty')['id']));
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->input('start_date') !== null) {
            $data['start_date'] = Carbon::parse($this->input('start_date'))->format('Y-m-d');
        }

        if ($this->input('end_date') !== null) {
            $data['end_date'] = Carbon::parse($this->input('end_date'))->format('Y-m-d');
        }

        if (! empty($data)) {
            $this->merge($data);
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
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'description' => 'nullable|array',
            'study_program_id' => 'nullable|ulid|exists:study_programs,id',
            'additional_email' => 'nullable|email',
            'additional_photo' => 'nullable|string',
            'use_original_duty_name' => 'nullable|boolean',
        ];
    }
}
