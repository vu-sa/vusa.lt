<?php

namespace App\Http\Requests;

use App\Enums\LocaleEnum;
use App\Enums\PageLayoutEnum;
use App\Http\Requests\Concerns\ValidatesContentParts;
use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Models\Page;
use App\Rules\SoftDeleteRules;
use App\Rules\ValidPageParent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePageRequest extends FormRequest
{
    use ValidatesContentParts;
    use ValidatesTenantScope;

    /**
     * Determine if the user is authorized to make this request.
     *
     * `can('create', Page::class)` is tenant-agnostic, so the `tenant_id` rule below is what
     * actually confines the page to a padalinys the user may create in.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Page::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->contentPartRules(),
            'content_version' => ['nullable', 'string', 'size:64'],
            'pairing_confirmation' => ['nullable', 'string', 'size:64'],
            'content.parts.*.key' => ['nullable', 'string', 'max:100'],
            'highlights' => ['nullable', 'array', 'max:3'],
            'highlights.*' => ['nullable', 'string', 'max:500'],
            'featured_image' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'title' => 'required|string|max:200',
            'lang' => ['required', new Enum(LocaleEnum::class)],
            'parent_id' => [
                'nullable', 'integer', SoftDeleteRules::existsLive('pages'),
                new ValidPageParent(lang: (string) $this->input('lang'), tenantId: $this->filled('tenant_id') ? (int) $this->input('tenant_id') : null),
            ],
            'other_lang_id' => ['nullable', 'integer', SoftDeleteRules::existsLive('pages')],
            'is_active' => 'required|boolean',
            'layout' => ['nullable', new Enum(PageLayoutEnum::class)],
            'show_table_of_contents' => ['boolean'],
            'show_title' => ['boolean'],
            'show_breadcrumbs' => ['boolean'],
            'tenant_id' => ['required', 'integer', 'exists:tenants,id', $this->tenantIdInAuthorizedScope('pages.create.padalinys')],
            'tags' => 'nullable|array',
            'tags.*' => ['integer', SoftDeleteRules::existsLive('tags')],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function messages(): array
    {
        return $this->contentPartMessages();
    }
}
