<?php

namespace App\Http\Requests;

use App\Enums\LocaleEnum;
use App\Models\Navigation;
use App\Services\NavigationService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
        $parentId = (int) ($this->input('parent_id') ?? 0);
        $location = ($extraAttributes['location'] ?? null) === 'footer' ? 'footer' : 'header';

        // Footer items only ever take two fixed shapes: a root is the column heading
        // (`category-link`), everything nested under it is a plain `link` — the client
        // hides the type picker for both, but the server is the one that actually
        // enforces it, regardless of what a crafted payload sends.
        if ($location === 'footer') {
            $extraAttributes['type'] = $parentId === 0 ? 'category-link' : 'link';
        }

        $extraAttributes['location'] = $location;

        $type = $extraAttributes['type'] ?? null;
        $isNameless = in_array($type, ['divider', 'heading'], true);
        $isOptionalUrlCategory = $location === 'footer' && $parentId === 0 && $type === 'category-link';
        $name = $this->input('name');
        $url = $this->input('url');

        $this->merge([
            'parent_id' => $parentId,
            'extra_attributes' => $extraAttributes,
            'name' => $isNameless && ($name === null || $name === '') ? '' : $name,
            // `url` is NOT NULL in the database, and the global ConvertEmptyStringsToNull
            // middleware has already turned an intentionally-blank field into `null` by
            // this point — coerce it back to '' so a text-only footer heading can save.
            'url' => $isOptionalUrlCategory && $url === null ? '' : $url,
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
        $location = $this->input('extra_attributes.location', 'header');
        $parentId = (int) $this->input('parent_id', 0);
        $isNameless = in_array($type, ['divider', 'heading'], true);

        // A footer column heading (`category-link` at the root) is the one place a URL
        // is optional — leaving it empty is how an editor makes it render as plain text
        // instead of a link (see SiteFooter.vue).
        $isOptionalUrlCategory = $location === 'footer' && $parentId === 0 && $type === 'category-link';

        return [
            'name' => $isNameless ? 'nullable|string|max:100' : 'required|string|max:100',
            'url' => $isOptionalUrlCategory ? 'nullable|string|max:500' : 'required|string|max:500',
            'parent_id' => 'required|integer|min:0',
            'lang' => ['nullable', new Enum(LocaleEnum::class)],
            'padalinys_id' => 'nullable|integer|exists:tenants,id',
            'is_active' => 'nullable|boolean',
            'extra_attributes' => 'nullable|array',
            'extra_attributes.location' => 'nullable|string|in:header,footer',
            'extra_attributes.type' => [
                'nullable',
                'string',
                $location === 'footer'
                    ? Rule::in([$parentId === 0 ? 'category-link' : 'link'])
                    : Rule::in(['link', 'block-link', 'category-link', 'full-height-background-link', 'divider', 'heading']),
            ],
            'extra_attributes.column' => 'nullable|integer|between:1,3',
            'extra_attributes.col_span' => 'nullable|integer|between:1,3',
            'extra_attributes.cols' => 'nullable|integer|between:1,3',
            'extra_attributes.icon' => 'nullable|string|max:100',
            'extra_attributes.description' => 'nullable|string|max:500',
            'extra_attributes.small_text' => 'nullable|string|max:200',
            'extra_attributes.badge_variant' => 'nullable|string|in:rose,emerald,amber,sky,zinc',
            'extra_attributes.featured' => 'nullable|boolean',
            'extra_attributes.new_tab' => 'nullable|boolean',
            'extra_attributes.image' => 'nullable|string|max:500',
            // Image-card copy: an eyebrow above the headline and a call-to-action label below it,
            // both part of the design's featured card and meaningless without an image.
            'extra_attributes.eyebrow' => 'nullable|string|max:100',
            'extra_attributes.cta' => 'nullable|string|max:100',
            'extra_attributes.image_height' => 'nullable|string|in:short,tall',
            'extra_attributes.image_render' => 'nullable|string|in:card,thumbnail',
            'extra_attributes.image_overlay' => 'nullable|string|in:none,light,medium,heavy',
            'extra_attributes.image_blur' => 'nullable|integer|in:0,2,4,8',
            'extra_attributes.image_focal' => ['nullable', 'string', 'max:20', 'regex:/^\d{1,3}% \d{1,3}%$/'],
            'extra_attributes.image_gradient' => 'nullable|string|in:none,bottom,full',
        ];
    }

    /**
     * Caps footer columns at `NavigationService::FOOTER_MAX_COLUMNS` — a rule can't count
     * sibling rows in the database, so this runs as an `after` callback instead.
     *
     * Not a parent-class override — `FormRequest` merely `method_exists()`-checks for
     * this hook, so no `#[\Override]` here.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $location = $this->input('extra_attributes.location', 'header');
            $parentId = (int) $this->input('parent_id', 0);
            $type = $this->input('extra_attributes.type');

            if ($location !== 'footer' || $parentId !== 0 || $type !== 'category-link') {
                return;
            }

            $lang = $this->input('lang') ?? app()->getLocale();
            $currentId = $this->route('navigation')?->id;

            $existingColumns = Navigation::where('parent_id', 0)
                ->where('lang', $lang)
                ->when($currentId, fn ($query) => $query->where('id', '!=', $currentId))
                ->get()
                ->filter(fn (Navigation $root) => ($root->extra_attributes['location'] ?? 'header') === 'footer')
                ->count();

            if ($existingColumns >= NavigationService::FOOTER_MAX_COLUMNS) {
                $validator->errors()->add('extra_attributes.location', trans('navigation.validation.footer_columns_max'));
            }
        });
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
