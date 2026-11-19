<?php

namespace App\Http\Requests;

use App\Enums\TenantType;
use App\Models\Tenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTenantRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fullname' => 'required|string|max:100|unique:tenants,fullname',
            'shortname' => 'required|string|max:100|unique:tenants,shortname',
            'type' => ['required', new Enum(TenantType::class)],
            'alias' => ['required', 'string', 'max:20', 'regex:'.Tenant::ALIAS_PATTERN, 'unique:tenants,alias'],
            'shortname_vu' => 'nullable|string|max:125|unique:tenants,shortname_vu',
            'primary_institution_id' => ['prohibited'],
            'goals_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
