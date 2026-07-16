<?php

namespace App\Http\Requests;

use App\Models\Type;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->route('type');

        return $type instanceof Type
            && ($this->user()?->can('update', $type) ?? false);
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
            'parent_id' => 'nullable|exists:types,id|different:id',
            'roles' => 'nullable|array',
            'extra_attributes' => 'nullable|array',
            'extra_attributes.meeting_periodicity_days' => 'nullable|integer|min:1|max:365',
        ];
    }
}
