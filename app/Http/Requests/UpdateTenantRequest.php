<?php

namespace App\Http\Requests;

use App\Enums\TenantType;
use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->tenant);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fullname' => 'required|unique:tenants,fullname,'.$this->tenant->id,
            'shortname' => 'required|unique:tenants,shortname,'.$this->tenant->id,
            // Type one of: pagrindinis, padalinys, pkp
            'type' => ['required', new Enum(TenantType::class)],
            'alias' => 'nullable|unique:tenants,alias,'.$this->tenant->id,
            'shortname_vu' => 'nullable|unique:tenants,shortname_vu,'.$this->tenant->id,
            'primary_institution_id' => ['nullable', SoftDeleteRules::existsLive('institutions')],
        ];
    }
}
