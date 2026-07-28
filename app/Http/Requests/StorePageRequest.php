<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesContentParts;
use App\Models\Page;
use App\Rules\SoftDeleteRules;
use App\Rules\UniqueAmongTrashed;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'lang' => 'required|string|in:lt,en',
            'permalink' => ['required', 'string', 'max:255', UniqueAmongTrashed::of('pages')],
            'category_id' => ['nullable', SoftDeleteRules::existsLive('categories')],
            'other_lang_id' => ['nullable', SoftDeleteRules::existsLive('pages')],
            'is_active' => 'required|boolean',
            'layout' => 'nullable|string|in:default,wide,focused',
            'show_table_of_contents' => ['boolean'],
            'show_title' => ['boolean'],
            'show_breadcrumbs' => ['boolean'],
        ];
    }
}
