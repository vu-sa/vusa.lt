<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNavigationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $navigation = $this->route('navigation');

        return $this->user()->can('update', $navigation);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $extraAttributes = $this->input('extra_attributes') ?? [];
        $isDivider = ($extraAttributes['type'] ?? null) === 'divider';
        $name = $this->input('name');

        $this->merge([
            'parent_id' => $this->input('parent_id') ?? 0,
            'extra_attributes' => $extraAttributes,
            'name' => $isDivider && ($name === null || $name === '') ? '' : $name,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isDivider = $this->input('extra_attributes.type') === 'divider';

        return [
            'name' => $isDivider ? 'nullable|string|max:100' : 'required|string|max:100',
            'url' => 'required|string|max:500',
            'parent_id' => 'required|integer|min:0',
            'padalinys_id' => 'nullable|integer|exists:tenants,id',
            'is_active' => 'nullable|boolean',
            'extra_attributes' => 'nullable|array',
            'extra_attributes.type' => 'nullable|string|in:link,block-link,category-link,full-height-background-link,divider',
            'extra_attributes.column' => 'nullable|integer|between:1,3',
            'extra_attributes.icon' => 'nullable|string|max:100',
            'extra_attributes.description' => 'nullable|string|max:500',
            'extra_attributes.small_text' => 'nullable|string|max:200',
            'extra_attributes.image' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Pavadinimas yra privalomas.',
            'name.max' => 'Pavadinimas negali būti ilgesnis nei 100 simbolių.',
            'url.required' => 'Nuoroda yra privaloma.',
            'url.max' => 'Nuoroda negali būti ilgesnė nei 500 simbolių.',
            'parent_id.required' => 'Tėvinis elementas yra privalomas.',
            'parent_id.integer' => 'Tėvinis elementas turi būti skaičius.',
            'padalinys_id.exists' => 'Pasirinktas padalinys neegzistuoja.',
            'extra_attributes.type.in' => 'Pasirinktas nuorodos stilius yra neteisingas.',
            'extra_attributes.column.between' => 'Stulpelis turi būti tarp 1 ir 3.',
        ];
    }
}
