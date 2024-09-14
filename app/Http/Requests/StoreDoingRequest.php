<?php

namespace App\Http\Requests;

use App\Models\Doing;
use Illuminate\Foundation\Http\FormRequest;

class StoreDoingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', Doing::class);
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'date' => date('Y-m-d', $this->input('date') / 1000),
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
            'date' => 'required|date:Y-m-d',
            'title' => 'required|string',
            'type' => 'nullable|string',
        ];
    }
}
