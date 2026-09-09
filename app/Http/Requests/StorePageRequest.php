<?php

namespace App\Http\Requests;

use App\Enums\LocaleEnum;
use App\Enums\PageLayoutEnum;
use App\Http\Requests\Concerns\ValidatesContentParts;
use App\Models\Page;
use App\Rules\SoftDeleteRules;
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
