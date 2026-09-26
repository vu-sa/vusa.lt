<?php

namespace App\Http\Requests;

use App\Models\Duty;
use App\Models\Type;
use App\Support\MorphMap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncTypeRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->route('type');

        return $type instanceof Type
            && $type->model_type === MorphMap::alias(Duty::class)
            && ($this->user()?->can('update', $type) ?? false);
    }

    public function rules(): array
    {
        return [
            'roles' => ['required', 'array'],
            'roles.*' => ['string', 'distinct', Rule::exists('roles', 'id')],
        ];
    }
}
