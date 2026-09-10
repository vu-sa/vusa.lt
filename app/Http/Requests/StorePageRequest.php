<?php

namespace App\Http\Requests;

use App\Enums\LocaleEnum;
use App\Enums\PageLayoutEnum;
use App\Http\Requests\Concerns\ValidatesContentParts;
use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Models\Page;
use App\Rules\SoftDeleteRules;
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
            'title' => 'required|string|max:255',
            'lang' => ['required', new Enum(LocaleEnum::class)],
            'category_id' => ['nullable', SoftDeleteRules::existsLive('categories')],
            'other_lang_id' => ['nullable', SoftDeleteRules::existsLive('pages')],
            'is_active' => 'required|boolean',
            'layout' => ['nullable', new Enum(PageLayoutEnum::class)],
            'show_table_of_contents' => ['boolean'],
            'show_title' => ['boolean'],
            'show_breadcrumbs' => ['boolean'],
            'tenant_id' => ['required', 'integer', 'exists:tenants,id', $this->tenantIdInAuthorizedScope('pages.create.padalinys')],
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
