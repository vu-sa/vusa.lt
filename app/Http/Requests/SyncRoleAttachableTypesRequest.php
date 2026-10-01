<?php

namespace App\Http\Requests;

use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncRoleAttachableTypesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('role'));
    }

    /**
     * See SyncRoleDutiesRequest — `present` allows clearing the list while still guaranteeing
     * the key exists.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attachable_types' => 'present|array',
            'attachable_types.*' => ['integer', SoftDeleteRules::existsLive('duty_types')],
        ];
    }
}
