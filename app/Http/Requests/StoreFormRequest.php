<?php

namespace App\Http\Requests;

use App\Models\Form;
use Illuminate\Foundation\Http\FormRequest;

class StoreFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Form::class);
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
            'training_id' => 'nullable|exists:trainings,id',
        ];
    }
}
