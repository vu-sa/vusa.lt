<?php

namespace App\Http\Requests;

use App\Models\Training;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreTrainingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Training::class);
    }

    protected function prepareForValidation()
    {
        $data = [];

        if ($this->input('start_time') !== null) {
            $data['start_time'] = Carbon::parse($this->input('start_time') / 1000)->setTimezone('Europe/Vilnius')->toDateTimeString();
        }

        if ($this->input('end_time') !== null) {
            $data['end_time'] = Carbon::parse($this->input('end_time') / 1000)->setTimezone('Europe/Vilnius')->toDateTimeString();
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
            'name' => 'required|array',
            'name.lt' => 'required|string|unique:trainings,name->lt',
            'name.en' => 'nullable|string',
            'description' => 'required|array',
            'description.lt' => 'required|string',
            'description.en' => 'nullable|string',
            'institution_id' => 'required|exists:institutions,id',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'address' => 'nullable|string|max:255',
            'max_participants' => 'nullable|integer|min:0',
        ];
    }
}
