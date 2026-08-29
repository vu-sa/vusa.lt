<?php

namespace App\Http\Requests;

use App\Enums\InstitutionScope;
use App\Models\Type;
use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Type::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title.lt' => 'required|string',
            'title.en' => 'nullable|string',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            // Constrained to the models a Type can actually be attached to —
            // see Type::TYPEABLE_RELATIONS.
            'model_type' => ['required', 'string', Rule::in(array_keys(Type::TYPEABLE_RELATIONS))],
            'parent_id' => ['nullable', SoftDeleteRules::existsLive('types')],
            'roles' => 'nullable|array',
            'slug' => 'nullable|string',
            'extra_attributes' => 'nullable|array',
            'extra_attributes.meeting_periodicity_days' => 'nullable|integer|min:1|max:365',
            // Null means "inherit from the parent type" — see InstitutionScopeResolver.
            'extra_attributes.governance_scope' => ['nullable', Rule::enum(InstitutionScope::class)],
        ];
    }
}
