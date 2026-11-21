<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTenantScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreResourceRequest extends FormRequest
{
    use ValidatesTenantScope;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name.lt' => 'required|string',
            'description.lt' => 'required|string',
            'name.en' => 'nullable|string',
            'description.en' => 'nullable|string',
            'identifier' => 'nullable|string',
            'location' => 'required|string',
            'tenant_id' => ['required', 'integer', 'exists:tenants,id', $this->tenantIdInAuthorizedScope('resources.create.padalinys')],
            'capacity' => 'required|integer|min:1',
            'is_reservable' => 'required|boolean',
            'resource_category_id' => 'nullable|integer|exists:resource_categories,id',
            'media' => 'array|nullable',
            'media.*.file' => 'file|mimes:jpg,jpeg,png,webp|extensions:jpg,jpeg,png,webp|max:10240',
        ];
    }
}
