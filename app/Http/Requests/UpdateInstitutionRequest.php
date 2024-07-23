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
            'name.lt' => 'required|unique:institutions,name,'.$this->institution->id,
            'short_name.lt' => 'nullable|unique:institutions,short_name,'.$this->institution->id,
            'description.lt' => 'nullable',
            'name.en' => 'nullable',
            'short_name.en' => 'nullable',
            'description.en' => 'nullable',
            'address.lt' => 'nullable|string',
            'address.en' => 'nullable|string',
            'alias' => 'nullable|unique:institutions,alias,'.$this->institution->id,
            'website' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'tenant_id' => 'required',
            'image_url' => 'nullable|string',
            'logo_url' => 'nullable|string',
            'facebook_url' => 'nullable|string',
            'instagram_url' => 'nullable|string',
            'is_active' => 'boolean',
            'types' => 'nullable|array',
        ];
    }
}
