<?php

namespace App\Http\Requests;

use App\Models\Institution;

class UpdateInstitutionRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', [Institution::class, $this->institution, $this->authorizer]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name.lt' => 'required|unique:institutions,name',
            'short_name.lt' => 'required|unique:institutions,short_name',
            'alias.lt' => 'nullable|unique:institutions,alias',
            'description.lt' => 'nullable',
            'name.en' => 'nullable',
            'short_name.en' => 'nullable',
            'alias.en' => 'nullable',
            'description.en' => 'nullable',
            'address.lt' => 'nullable|string',
            'address.en' => 'nullable|string',
            'website.lt' => 'nullable|string',
            'website.en' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'tenant_id' => 'required',
            'image_url' => 'nullable|string',
            'logo_url' => 'nullable|string',
            'is_active' => 'boolean',
            'types' => 'nullable|array',
        ];
    }
}
