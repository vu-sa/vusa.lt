<?php

namespace App\Http\Requests;

use App\Models\DutyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncTypeRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->route('type');

        return $type instanceof DutyType
            && ($this->user()?->can('update', $type) ?? false);
    }

    public function rules(): array
    {
        return [
            'roles' => ['present', 'array'],
            'roles.*' => ['string', 'distinct', Rule::exists('roles', 'id')],
        ];
    }
}
