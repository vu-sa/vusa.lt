<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateTrainingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->training);
    }

    protected function prepareForValidation()
    {
        if ($this->input('start_time') === null) {
            return;
        }

        /*dd(request()->all());*/

        $this->merge([
            'start_time' => Carbon::parse($this->input('start_time') / 1000)->setTimezone('Europe/Vilnius')->toDateTimeString(),
        ]);

        if ($this->input('end_time') !== null) {
            $this->merge([
                'end_time' => Carbon::parse($this->input('end_time') / 1000)->setTimezone('Europe/Vilnius')->toDateTimeString(),
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
            'name' => 'required|array',
            'name.lt' => 'required|string',
            'name.en' => 'nullable|string',
            'description' => 'required|array',
            'description.lt' => 'required|string',
            'description.en' => 'nullable|string',
            'address' => 'nullable|string',
            'meeting_url' => 'nullable|url',
            'image' => 'nullable|string',
            'institution_id' => 'required|exists:institutions,id',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'max_participants' => 'nullable|integer',
            'is_online' => 'required|boolean',
            'is_hybrid' => 'required|boolean',
        ];
    }
}
