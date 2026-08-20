<?php

namespace App\Http\Requests;

use App\Enums\LocaleEnum;
use App\Enums\PageLayoutEnum;
use App\Http\Requests\Concerns\ValidatesContentParts;
use App\Models\Page;
use App\Models\Tenant;
use App\Rules\SoftDeleteRules;
use App\Rules\UniqueAmongTrashed;
use App\Services\ModelAuthorizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePageRequest extends FormRequest
{
    use ValidatesContentParts;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Page::class);
    }

    /**
     * Get the tenant the new page will be created for.
     *
     * Mirrors the tenant resolution in PageController::store so the permalink
     * uniqueness check is scoped to the same tenant the record will belong to.
     */
    protected function getTargetTenantId(): ?int
    {
        if ($this->user()->isSuperAdmin()) {
            return Tenant::main()?->id;
        }

        $authorizer = app(ModelAuthorizer::class)->forUser($this->user());
        $authorizer->check('pages.create.padalinys');

        return $authorizer->getPermissableDuties()->first()?->getAttribute('tenants')->first()?->id;
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
            'permalink' => ['required', 'string', 'max:255', UniqueAmongTrashed::of('pages')->where('tenant_id', $this->getTargetTenantId())],
            'category_id' => ['nullable', SoftDeleteRules::existsLive('categories')],
            'other_lang_id' => ['nullable', SoftDeleteRules::existsLive('pages')],
            'is_active' => 'required|boolean',
            'layout' => ['nullable', new Enum(PageLayoutEnum::class)],
            'show_table_of_contents' => ['boolean'],
            'show_title' => ['boolean'],
            'show_breadcrumbs' => ['boolean'],
        ];
    }
}
