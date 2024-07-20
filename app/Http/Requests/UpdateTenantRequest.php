<?php

namespace App\Http\Requests;

use App\Models\Tenant;

class UpdateTenantRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', [Tenant::class, $this->tenant, $this->authorizer]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fullname' => 'required|unique:tenants,fullname,' . $this->tenant->id,
            'shortname' => 'required|unique:tenants,shortname,' . $this->tenant->id,
            // Type one of: pagrindinis, padalinys, pkp
            'type' => 'required|in:pagrindinis,padalinys,pkp',
            'alias' => 'nullable|unique:tenants,alias,' . $this->tenant->id,
            'shortname_vu' => 'nullable|unique:tenants,shortname_vu,' . $this->tenant->id,
            'primary_institution_id' => 'nullable|exists:institutions,id',
        ];
    }
}
