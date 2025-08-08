<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->form);
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
            'name.lt' => 'nullable|string',
            'name.en' => 'nullable|string',
            'description' => 'array',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'path' => 'required|array',
            'tenant_id' => 'required|exists:tenants,id',
            'form_fields' => 'array',
            'publish_time' => 'nullable|date',
        ];
    }
}
