<?php

namespace App\Http\Requests;

use App\Actions\GenerateUniqueSlug;
use App\Enums\LocaleEnum;
use App\Enums\PageLayoutEnum;
use App\Http\Requests\Concerns\ValidatesContentParts;
use App\Models\Page;
use App\Rules\SoftDeleteRules;
use App\Rules\UniqueAmongTrashed;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdatePageRequest extends FormRequest
{
    use ValidatesContentParts;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->page);
    }

    /**
     * Get the tenant the page belongs to, so permalink uniqueness is scoped
     * to that tenant instead of checked globally.
     */
    protected function getTargetTenantId(): ?int
    {
        return $this->page->tenant_id;
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
            'permalink' => [
                'sometimes', 'required', 'string', 'max:255', // matches the `pages.permalink` column width
                UniqueAmongTrashed::of('pages')->ignore($this->page->id)->where('tenant_id', $this->getTargetTenantId()),
                fn (string $attribute, mixed $value, Closure $fail) => $this->assertPermalinkNotRetiredByAnother((string) $value, $fail),
            ],
            'category_id' => ['nullable', SoftDeleteRules::existsLive('categories')],
            // `different:id` was inert — the payload has no `id` field — so a page
            // could be paired with itself. Compare against the route model instead.
            'other_lang_id' => ['nullable', SoftDeleteRules::existsLive('pages'), Rule::notIn([$this->page->id])],
            'is_active' => 'required|boolean',
            'layout' => ['nullable', new Enum(PageLayoutEnum::class)],
            'show_table_of_contents' => ['boolean'],
            'show_title' => ['boolean'],
            'show_breadcrumbs' => ['boolean'],
        ];
    }

    /**
     * A permalink that's clear of the live `pages` table can still belong to a *different*
     * page's redirect history — see GenerateUniqueSlug::isRetiredByAnother(). Re-adopting this
     * record's *own* history is fine and excluded via $this->page->id.
     */
    private function assertPermalinkNotRetiredByAnother(string $permalink, Closure $fail): void
    {
        $tenantId = $this->getTargetTenantId();

        if (blank($permalink) || $tenantId === null) {
            return;
        }

        if (GenerateUniqueSlug::isRetiredByAnother(Page::class, $permalink, $tenantId, $this->page->id)) {
            $fail(__('validation.unique', ['attribute' => 'permalink']));
        }
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
