<?php

namespace App\Http\Requests;

use App\Models\Meeting;
use Illuminate\Foundation\Http\FormRequest;

class StoreMeetingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', Meeting::class);
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'start_time' => date('Y-m-d H:i', $this->input('start_time') / 1000),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'start_time' => 'required|date:Y-m-d H:i',
            'institution_id' => 'required|ulid',
            'type_id' => 'nullable|integer',
        ];
    }
}
