<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Tenant::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fullname' => 'required|unique:tenants,fullname',
            'shortname' => 'required|unique:tenants,shortname',
            // Type one of: pagrindinis, padalinys, pkp
            'type' => 'required|in:pagrindinis,padalinys,pkp',
            'alias' => 'nullable|unique:tenants,alias',
            'shortname_vu' => 'nullable|unique:tenants,shortname_vu',
            'primary_institution_id' => 'nullable|exists:institutions,id',
        ];
    }
}
