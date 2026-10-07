<?php

namespace App\Http\Requests;

use App\Enums\InstitutionScope;
use App\Models\DutyType;
use App\Models\InstitutionType;
use App\Rules\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class TypeAttributesRequest extends FormRequest
{
    /** @return class-string<InstitutionType|DutyType> */
    abstract protected function typeClass(): string;

    public function authorize(): bool
    {
        $type = $this->route('type');

        return $type === null
            ? ($this->user()?->can('create', $this->typeClass()) ?? false)
            : ($type instanceof ($this->typeClass()) && ($this->user()?->can('update', $type) ?? false));
    }

    public function rules(): array
    {
        $class = $this->typeClass();
        $type = $this->route('type');
        $excluded = $type?->getDescendantsAndSelf(withTrashed: true)->modelKeys() ?? [];

        $rules = [
            'title.lt' => ['required', 'string'],
            'title.en' => ['nullable', 'string'],
            'description.lt' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],
            'slug' => ['nullable', 'string', 'max:125'],
            'parent_id' => ['nullable', 'integer', SoftDeleteRules::existsLive((new $class)->getTable()), Rule::notIn($excluded)],
            'model_type' => ['prohibited'],
        ];

        if ($class === InstitutionType::class) {
            $rules += [
                'extra_attributes' => ['nullable', 'array'],
                'extra_attributes.meeting_periodicity_days' => ['nullable', 'integer', 'min:1', 'max:365'],
                'extra_attributes.governance_scope' => ['nullable', Rule::enum(InstitutionScope::class)],
                'extra_attributes.enable_sibling_relationships' => ['boolean'],
                'extra_attributes.enable_cross_tenant_sibling_relationships' => ['boolean'],
            ];
        }

        return $rules;
    }
}
