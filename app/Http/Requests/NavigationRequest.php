<?php

namespace App\Http\Requests;

use App\Enums\LocaleEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Shared rules for Store/UpdateNavigationRequest — the two differ only in `authorize()`.
 *
 * `extra_attributes` keys are the single source of truth for what the admin builder, the
 * public menu (`MainNavigationMenuContent.vue`) and `NavigationService` are allowed to read.
 * A key missing here is silently stripped on save.
 */
abstract class NavigationRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        $extraAttributes = $this->input('extra_attributes') ?? [];
        $type = $extraAttributes['type'] ?? null;
        $isNameless = in_array($type, ['divider', 'heading'], true);
        $name = $this->input('name');

        $this->merge([
            'parent_id' => $this->input('parent_id') ?? 0,
            'extra_attributes' => $extraAttributes,
            'name' => $isNameless && ($name === null || $name === '') ? '' : $name,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = $this->input('extra_attributes.type');
        $isNameless = in_array($type, ['divider', 'heading'], true);

        return [
            'name' => $isNameless ? 'nullable|string|max:100' : 'required|string|max:100',
            'url' => 'required|string|max:500',
            'parent_id' => 'required|integer|min:0',
            'lang' => ['nullable', new Enum(LocaleEnum::class)],
            'padalinys_id' => 'nullable|integer|exists:tenants,id',
            'is_active' => 'nullable|boolean',
            'extra_attributes' => 'nullable|array',
            'extra_attributes.type' => 'nullable|string|in:link,block-link,category-link,full-height-background-link,divider,heading',
            'extra_attributes.column' => 'nullable|integer|between:1,3',
            'extra_attributes.col_span' => 'nullable|integer|between:1,3',
            'extra_attributes.cols' => 'nullable|integer|between:1,3',
            // Root items only — the dropdown's own width, independent of its column
            // count. `wide` matches the menu's pre-redesign fixed width.
            'extra_attributes.menu_width' => 'nullable|string|in:narrow,medium,wide,auto',
            'extra_attributes.icon' => 'nullable|string|max:100',
            'extra_attributes.description' => 'nullable|string|max:500',
            'extra_attributes.small_text' => 'nullable|string|max:200',
            'extra_attributes.badge_variant' => 'nullable|string|in:rose,emerald,amber,sky,zinc',
            'extra_attributes.featured' => 'nullable|boolean',
            'extra_attributes.new_tab' => 'nullable|boolean',
            'extra_attributes.image' => 'nullable|string|max:500',
            'extra_attributes.image_render' => 'nullable|string|in:card,thumbnail',
            'extra_attributes.image_overlay' => 'nullable|string|in:none,light,medium,heavy',
            'extra_attributes.image_blur' => 'nullable|integer|in:0,2,4,8',
            'extra_attributes.image_focal' => ['nullable', 'string', 'max:20', 'regex:/^\d{1,3}% \d{1,3}%$/'],
            'extra_attributes.image_gradient' => 'nullable|string|in:none,bottom,full',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'name.required' => trans('navigation.validation.name_required'),
            'name.max' => trans('navigation.validation.name_max'),
            'url.required' => trans('navigation.validation.url_required'),
            'url.max' => trans('navigation.validation.url_max'),
            'parent_id.required' => trans('navigation.validation.parent_required'),
            'parent_id.integer' => trans('navigation.validation.parent_integer'),
            'padalinys_id.exists' => trans('navigation.validation.tenant_exists'),
            'extra_attributes.type.in' => trans('navigation.validation.type_invalid'),
            'extra_attributes.column.between' => trans('navigation.validation.column_between'),
        ];
    }
}
