<?php

namespace App\Http\Requests\MailingLists;

use App\Http\Requests\Concerns\ValidatesTenantScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

abstract class MailingListRequest extends FormRequest
{
    use ValidatesTenantScope;

    protected string $tenantScopePermission = 'mailingLists.update.padalinys';

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'tenant_id' => ['required', 'integer', 'exists:tenants,id', $this->tenantIdInAuthorizedScope($this->tenantScopePermission)],
        ];
    }
}
